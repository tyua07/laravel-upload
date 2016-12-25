<?php

// +----------------------------------------------------------------------
// | date: 2016-12-24
// +----------------------------------------------------------------------
// | CosAdapter.php: COS 上传实现
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

namespace Yangyifan\Upload\Cos\Cos;

trait File
{
    /*
     * 查询文件信息
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件路径
     */
    public function stat($bucket, $path)
    {
        $path = self::normalizerPath($path);

        return $this->statBase($bucket, $path);
    }

    /**
     * 上传文件,自动判断文件大小,如果小于20M则使用普通文件上传,大于20M则使用分片上传
     * @param  string  $bucket   bucket名称
     * @param  string  $dstPath      上传的文件路径
     * @param  string  $filecontent   文件的内容
     * @param  string  $bizAttr      文件属性
     * @param  string  $slicesize    分片大小(512k,1m,2m,3m)，默认:1m
     * @param  string  $insertOnly   同名文件是否覆盖
     * @return [type]                [description]
     */
    public function upload(
        $bucket, $dstPath, $filecontent, $mime_type, $bizAttr=null, $sliceSize=null, $insertOnly=true) {

        $dstPath = self::normalizerPath($dstPath, false);

        // 如果是文件句柄，则读取文件内容
        if ( is_resource($filecontent) ) {
            $content = fgets($filecontent);
        } else {
            $content = $filecontent;
        }

        //文件大于20M则使用分片传输
        if (strlen($content) < self::MAX_UNSLICE_FILE_SIZE ) {
            return $this->uploadFile($bucket, $dstPath, $content, $mime_type, $bizAttr, $insertOnly);
        } else {
            throw new \InvalidArgumentException('文件大小大于 20M，请使用分片传输！');

            //$sliceSize = self::getSliceSize($sliceSize);
            //return self::uploadBySlicing($bucket,$dstPath, $content, $mime_type, $bizAttr, $sliceSize, $insertOnly);
        }
    }

    /**
     * 内部方法, 上传文件
     *
     * @param  string  $bucket  bucket名称
     * @param  string  $srcPath     本地文件路径
     * @param  string  $dstPath     上传的文件路径
     * @param  string  $bizAttr     文件属性
     * @param  int     $insertOnly  是否覆盖同名文件:0 覆盖,1:不覆盖
     * @return [type]               [description]
     */
    private function uploadFile($bucket, $dstPath, $filecontent, $mime_type, $bizAttr = null, $insertOnly = null)
    {
        $dstPath = self::cosUrlEncode($dstPath);

        $expired    = time() + self::EXPIRED_SECONDS;
        $url        = self::generateResUrl($bucket, $dstPath);
        $signature  = $this->createReusableSignature($expired, $bucket);
        $fileSha    = sha1($filecontent);

        $data = array(
            'op'        => 'upload',
            'sha'       => $fileSha,
            'biz_attr'  => (isset($bizAttr) ? $bizAttr : ''),
        );

        $data['filecontent'] = $filecontent;

        if (isset($insertOnly) && strlen($insertOnly) > 0) {
            $data['insertOnly'] = (($insertOnly == 0 || $insertOnly == '0' ) ? 0 : 1);
        }

        // 设置文件的 mime 类型
        $data['custom_headers']['Content-Type'] = $mime_type;

        $req = [
            'url'       => $url,
            'method'    => 'post',
            'timeout'   => self::$timeout,
            'data'      => $data,
            'header'    => [
                'Authorization: ' . $signature,
            ],
        ];

        return self::sendRequest($req);
    }


    /**
     * 内部方法,上传文件
     * @param  string  $bucket  bucket名称
     * @param  string  $srcPath     本地文件路径
     * @param  string  $dstPath     上传的文件路径
     * @param  string  $bizAttr     文件属性
     * @param  string  $sliceSize   分片大小
     * @param  int     $insertOnly  是否覆盖同名文件:0 覆盖,1:不覆盖
     * @return [type]                [description]
     */
    private function uploadBySlicing(
        $bucket,  $dstFpath, $content, $mime_type, $bizAttr=null, $sliceSize=null, $insertOnly=null)
    {
        $fileSize = strlen($content);
        $dstFpath = self::cosUrlEncode($dstFpath);
        $url = self::generateResUrl($bucket, $dstFpath);
        $sliceCount = ceil($fileSize / $sliceSize);
        // expiration seconds for one slice mutiply by slice count
        // will be the expired seconds for whole file
        $expiration = time() + (self::EXPIRED_SECONDS * $sliceCount);
        if ($expiration >= (time() + 10 * 24 * 60 * 60)) {
            $expiration = time() + 10 * 24 * 60 * 60;
        }
        $signature = $this->createReusableSignature($expiration, $bucket);

        $sliceUploading = new SliceUploading(self::$timeout * 1000, self::MAX_RETRY_TIMES);
        for ($tryCount = 0; $tryCount < self::MAX_RETRY_TIMES; ++$tryCount) {
            if ($sliceUploading->initUploading(
                $signature,
                $srcFpath,
                $url,
                $fileSize, $sliceSize, $bizAttr, $insertOnly)) {
                break;
            }

            $errorCode = $sliceUploading->getLastErrorCode();
            if ($errorCode === -4019) {
                // Delete broken file and retry again on _ERROR_FILE_NOT_FINISH_UPLOAD error.
                $this->delFile($bucket, $dstFpath);
                continue;
            }

            if ($tryCount === self::MAX_RETRY_TIMES - 1) {
                return array(
                    'code' => $sliceUploading->getLastErrorCode(),
                    'message' => $sliceUploading->getLastErrorMessage(),
                    'requestId' => $sliceUploading->getRequestId(),
                );
            }
        }

        if (!$sliceUploading->performUploading()) {
            return array(
                'code' => $sliceUploading->getLastErrorCode(),
                'message' => $sliceUploading->getLastErrorMessage(),
                'requestId' => $sliceUploading->getRequestId(),
            );
        }

        if (!$sliceUploading->finishUploading()) {
            return array(
                'code' => $sliceUploading->getLastErrorCode(),
                'message' => $sliceUploading->getLastErrorMessage(),
                'requestId' => $sliceUploading->getRequestId(),
            );
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'requestId' => $sliceUploading->getRequestId(),
            'data' => array(
                'accessUrl' => $sliceUploading->getAccessUrl(),
                'resourcePath' => $sliceUploading->getResourcePath(),
                'sourceUrl' => $sliceUploading->getSourceUrl(),
            ),
        );
    }

    /*
     * 删除文件
     * @param  string  $bucket
     * @param  string  $path      文件路径
     */
    public function delFile($bucket, $path)
    {
        if (empty($bucket) || empty($path)) {
            return array(
                'code' => Response::COSAPI_PARAMS_ERROR,
                'message' => 'path is empty'
            );
        }

        $path = self::normalizerPath($path);

        return $this->delBase($bucket, $path);
    }

    /**
     * Move a file.
     * @param $bucket bucket name.
     * @param $srcFpath source file path.
     * @param $dstFpath destination file path.
     * @param $overwrite if the destination location is occupied, overwrite it or not?
     * @return array|mixed.
     */
    public function moveFile($bucket, $srcFpath, $dstFpath, $overwrite = false)
    {
        $url    = self::generateResUrl($bucket, $srcFpath);
        $sign   = $this->createNonreusableSignature($bucket, $srcFpath);
        $data   = array(
            'op' => 'move',
            'dest_fileid' => $dstFpath,
            'to_over_write' => $overwrite ? 1 : 0,
        );
        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $sign,
            ),
        );

        return self::sendRequest($req);
    }

    /**
     * Copy a file.
     * @param $bucket bucket name.
     * @param $srcFpath source file path.
     * @param $dstFpath destination file path.
     * @param $overwrite if the destination location is occupied, overwrite it or not?
     * @return array|mixed.
     */
    public function copyFile($bucket, $srcFpath, $dstFpath, $overwrite = false)
    {
        $url = self::generateResUrl($bucket, $srcFpath);
        $sign = $this->createNonreusableSignature($bucket, $srcFpath);
        $data = array(
            'op' => 'copy',
            'dest_fileid' => $dstFpath,
            'to_over_write' => $overwrite ? 1 : 0,
        );
        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $sign,
            ),
        );

        return self::sendRequest($req);
    }

    /**
     * Get slice size.
     */
    private static function getSliceSize($sliceSize) {
        // Fix slice size to 1MB.
        return self::SLICE_SIZE_1M;
    }

    /*
     * 内部公共方法(更新文件和更新文件夹)
     * @param  string  $bucket  bucket名称
     * @param  string  $path        路径
     * @param  string  $bizAttr     文件/目录属性
     * @param  string  $authority:  eInvalid/eWRPrivate(私有)/eWPrivateRPublic(公有读写)
	 * @param  array   $customer_headers_array 携带的用户自定义头域,包括
     * 'Cache-Control' => '*'
     * 'Content-Type' => '*'
     * 'Content-Disposition' => '*'
     * 'Content-Language' => '*'
     * 'x-cos-meta-自定义内容' => '*'
     */
    public function updateBase(
        $bucket, $path, $bizAttr = null, $authority = null, $custom_headers_array = null)
    {
        $path = self::cosUrlEncode($path);
        $url = self::generateResUrl($bucket, $path);
        $signature = $this->createNonreusableSignature($bucket, $path);

        $data = array('op' => 'update');

        if (isset($bizAttr)) {
            $data['biz_attr'] = $bizAttr;
        }

        if (isset($authority) && strlen($authority) > 0) {
            if(self::isAuthorityValid($authority) == false) {
                return array(
                    'code' => COSAPI_PARAMS_ERROR,
                    'message' => 'parameter authority invalid');
            }

            $data['authority'] = $authority;
        }

        if (isset($custom_headers_array)) {
            $data['custom_headers'] = array();
            self::add_customer_header($data['custom_headers'], $custom_headers_array);
        }

        $data = json_encode($data);

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
                'Content-Type: application/json',
            ),
        );

        return self::sendRequest($req);
    }

    /**
     * 增加自定义属性到data中
     * @param  array  $data
     * @param  array  $customer_headers_array
     * @return [type]  void
     */
    private static function add_customer_header(&$data, &$customer_headers_array) {
        if (count($customer_headers_array) < 1) {
            return;
        }
        foreach($customer_headers_array as $key=>$value) {
            if(self::isCustomer_header($key)) {
                $data[$key] = $value;
            }
        }
    }

    /**
     * 判断authority值是否正确
     * @param  string  $authority
     * @return [type]  bool
     */
    private static function isAuthorityValid($authority) {
        if ($authority == 'eInvalid' || $authority == 'eWRPrivate' || $authority == 'eWPrivateRPublic') {
            return true;
        }
        return false;
    }

    /**
     * 判断是否符合自定义属性
     * @param  string  $key
     * @return [type]  bool
     */
    private static function isCustomer_header($key) {
        if ($key == 'Cache-Control' || $key == 'Content-Type' ||
            $key == 'Content-Disposition' || $key == 'Content-Language' ||
            $key == 'Content-Encoding' ||
            substr($key,0,strlen('x-cos-meta-')) == 'x-cos-meta-') {
            return true;
        }
        return false;
    }
}