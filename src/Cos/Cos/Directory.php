<?php

// +----------------------------------------------------------------------
// | date: 2016-12-24
// +----------------------------------------------------------------------
// | CosAdapter.php: COS 上传实现
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

namespace Yangyifan\Upload\Cos\Cos;

trait Directory
{
    /*
     * 创建目录
     *
     * @param  string  $bucket bucket名称
     * @param  string  $folder       目录路径
	 * @param  string  $bizAttr    目录属性
     */
    public function createFolder($bucket, $folder, $bizAttr = null)
    {
        if (!self::isValidPath($folder)) {
            return array(
                'code'      => Response::COSAPI_PARAMS_ERROR,
                'message'   => 'folder ' . $folder . ' is not a valid folder name',
                'data'      => array()
            );
        }

        $folder     = self::normalizerPath($folder, True);
        $folder     = self::cosUrlEncode($folder);
        $expired    = time() + self::EXPIRED_SECONDS;
        $url        = self::generateResUrl($bucket, $folder);
        $signature  = $this->createReusableSignature($expired, $bucket);

        $data = array(
            'op'        => 'create',
            'biz_attr'  => (isset($bizAttr) ? $bizAttr : ''),
        );

        $data = json_encode($data);

        $req = [
            'url'       => $url,
            'method'    => 'post',
            'timeout'   => self::$timeout,
            'data'      => $data,
            'header'    => [
                'Authorization: ' . $signature,
                'Content-Type: application/json',
            ],
        ];

        return self::parseRequest($req);
    }

    /*
     * 目录列表
     * @param  string  $bucket bucket名称
     * @param  string  $path     目录路径，sdk会补齐末尾的 '/'
     * @param  int     $num      拉取的总数
     * @param  string  $pattern  eListBoth,ListDirOnly,eListFileOnly  默认both
     * @param  int     $order    默认正序(=0), 填1为反序,
     * @param  string  $offset   透传字段,用于翻页,前端不需理解,需要往前/往后翻页则透传回来
     */
    public function listFolder(
        $bucket, $folder, $num = 20,
        $pattern = 'eListBoth', $order = 0,
        $context = null) {
        $folder = self::normalizerPath($folder, True);

        return $this->listBase($bucket, $folder, $num, $pattern, $order, $context);
    }

    /*
     * 目录列表(前缀搜索)
     * @param  string  $bucket bucket名称
     * @param  string  $prefix   列出含此前缀的所有文件
     * @param  int     $num      拉取的总数
     * @param  string  $pattern  eListBoth(默认),ListDirOnly,eListFileOnly
     * @param  int     $order    默认正序(=0), 填1为反序,
     * @param  string  $offset   透传字段,用于翻页,前端不需理解,需要往前/往后翻页则透传回来
     */
    public static function prefixSearch(
        $bucket, $prefix, $num = 20,
        $pattern = 'eListBoth', $order = 0,
        $context = null) {
        $path = self::normalizerPath($prefix);

        return self::listBase($bucket, $prefix, $num, $pattern, $order, $context);
    }

    /*
     * 目录更新
     * @param  string  $bucket bucket名称
     * @param  string  $folder      文件夹路径,SDK会补齐末尾的 '/'
     * @param  string  $bizAttr   目录属性
     */
    public static function updateFolder($bucket, $folder, $bizAttr = null) {
        $folder = self::normalizerPath($folder, True);

        return self::updateBase($bucket, $folder, $bizAttr);
    }

    /*
      * 查询目录信息
      * @param  string  $bucket bucket名称
      * @param  string  $folder       目录路径
      */
    public static function statFolder($bucket, $folder) {
        $folder = self::normalizerPath($folder, True);

        return self::statBase($bucket, $folder);
    }

    /*
     * 删除目录
     * @param  string  $bucket bucket名称
     * @param  string  $folder       目录路径
	 *  注意不能删除bucket下根目录/
     */
    public function delFolder($bucket, $folder)
    {
        if (empty($bucket) || empty($folder)) {
            return array(
                'code'      => Response::COSAPI_PARAMS_ERROR,
                'message'   => 'bucket or path is empty');
        }

        $folder = self::normalizerPath($folder, True);

        return $this->delBase($bucket, $folder);
    }
}