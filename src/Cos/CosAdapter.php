<?php

// +----------------------------------------------------------------------
// | date: 2016-12-24
// +----------------------------------------------------------------------
// | CosAdapter.php: COS 上传实现
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

namespace Yangyifan\Upload\Cos;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use InvalidArgumentException;
use Yangyifan\Config\Config as YangyifanConfig;
use League\Flysystem\Util\MimeType;
use Yangyifan\Library\PathLibrary;
use Yangyifan\Upload\Cos\Cos\Cos;
use Yangyifan\Upload\Cos\Cos\Response;

class CosAdapter extends AbstractAdapter
{
    /**
     * 业务维护的自定义属性
     */
    const FILE_TYPE = 'file';
    const DIR_TYPE  = 'dir';

    /**
     * bucket
     *
     * @var
     */
    protected $bucket;

    /**
     * 配置信息
     *
     * @var YangyifanConfig
     */
    protected $config;

    /**
     * cos 对象
     *
     * @var Cos
     */
    protected $cos;

    /**
     * 获取 Bucket
     *
     * @author @author yangyifan <yangyifanphp@gmail.com>
     * @return mixed
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * 设置 Bucket
     *
     * @param mixed $bucket
     * @author @author yangyifan <yangyifanphp@gmail.com>
     * @return CosAdapter
     */
    public function setBucket($bucket)
    {
        if ( empty($bucket) ) {
            throw new InvalidArgumentException('bucket 不能为空！');
        }

        $this->bucket = $bucket;

        return $this;
    }

    /**
     * 获取 Cos 对象
     *
     * @author @author yangyifan <yangyifanphp@gmail.com>
     * @return Cos
     */
    public function getCos()
    {
        return $this->cos;
    }

    /**
     * 设置 Cos 对象
     *
     * @param Cos $cos
     * @author @author yangyifan <yangyifanphp@gmail.com>
     * @return CosAdapter
     */
    public function setCos($cos)
    {
        $this->cos = $cos;

        return $this;
    }

    /**
     * 构造方法
     *
     * @param array $config 配置信息
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function __construct($config)
    {
        // 设置区域
        Cos::setRegion($config['region']);
        // 设置超时时间
        Cos::setTimeout($config['timeout']);

        $this->setCos(new Cos($config))->setBucket($config['bucket']);

        //设置路径前缀
        $this->setPathPrefix($config['transport'] . '://' . $config['domain']);

        include __DIR__ . '/cos-php-sdk-v4/qcloudcos/libcurl_helper.php';
    }

    /**
     * 获得 Cos 实例
     *
     * @return Cos
     */
    public function getInstance()
    {
        return $this->getCos();
    }

    /**
     * 判断文件是否存在
     *
     * @param string $path
     * @return bool
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function has($path)
    {
        return $this->getMetadata($path) != false ;
    }

    /**
     * 读取文件
     *
     * @param $file_name
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function read($path)
    {
        return ['contents' => file_get_contents($this->applyPathPrefix($path)) ];
    }

    /**
     * 获得文件流
     *
     * @param string $path
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function readStream($path)
    {
        return ['stream' => fopen($this->applyPathPrefix($path), 'r')];
    }

    /**
     * 写入文件
     *
     * @param $file_name
     * @param $contents
     * @param Config $config
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function write($path, $contents, Config $config)
    {
        $pathInfo   = pathinfo($path);
        $path       = PathLibrary::normalizerPath($path);

        // 先递归创建目录
        if ( !empty($pathInfo['dirname']) ) {
            $this->createDir($pathInfo['dirname'], new Config());
        }

        // 设置文件的 mime_type
        $mimetype = MimeType::detectByContent($contents);

        $status = Response::parseResponse(
            $this->getCos()->upload($this->getBucket(), $path, $contents, $mimetype, self::FILE_TYPE)
        );

        if ( $status ) {

            // 更新文件的 mime 类型
            $info = $this->getMetadata($path);

            return Response::parseResponse(
                $this->getCos()->updateBase(
                    $this->getBucket(),
                    $path,
                    $info['biz_attr'],
                    $info['authority'],
                    ['Content-Type' => $mimetype]
                )
            ) != false;
        }
    }

    /**
     * 写入文件流
     *
     * @param string $path
     * @param resource $resource
     * @param array $config
     */
    public function writeStream($path, $resource, Config $config)
    {
        $pathInfo   = pathinfo($path);
        $path       = PathLibrary::normalizerPath($path);

        // 先递归创建目录
        if ( !empty($pathInfo['dirname']) ) {
            $this->createDir($pathInfo['dirname'], new Config());
        }

        // 设置文件的 mime_type
        $mimetype = MimeType::detectByContent(fgets($resource));

        $status = Response::parseResponse(
            $this->getCos()->upload($this->getBucket(), $path, $resource, $mimetype, self::FILE_TYPE)
        );

        if ( $status ) {
            // 更新文件的 mime 类型
            $info = $this->getMetadata($path);

            return Response::parseResponse(
                $this->getCos()->updateBase(
                    $this->getBucket(),
                    $path,
                    $info['biz_attr'],
                    $info['authority'],
                    ['Content-Type' => $mimetype]
                )
            ) != false;
        }

        return false ;
    }

    /**
     * 更新文件
     *
     * @param string $path
     * @param string $contents
     * @param array $config
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * 更新文件流
     *
     * @param string $path
     * @param resource $resource
     * @param array $config
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * 列出目录文件
     *
     * @param string $directory
     * @param bool|false $recursive
     * @return mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function listContents($directory = '', $recursive = false)
    {
        $directory  = PathLibrary::normalizerPath($directory);
        $list       = $this->getCos()->listFolder($this->getBucket(), $directory);
        $list       = json_decode($list, true);


        if ($list && $list['code'] == Response::COSAPI_SUCCESS) {

            $file_list = $list['data']['infos'];

            foreach ($file_list as &$file) {
                $file['path']   = $directory . $file['name'];
            }

            return $file_list;
        }

        return false;
    }

    /**
     * 获取资源的元信息，但不返回文件内容
     *
     * @param $path
     * @return mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getMetadata($path)
    {
        $data = Response::parseResponse(
            $this->getCos()->stat($this->getBucket(), $path)
        );

        return $data['data'];
    }

    /**
     * 获得文件大小
     *
     * @param string $path
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getSize($path)
    {
        $stat = $this->getMetadata($path);

        if ( $stat ) {
            return ['size' => $stat['filesize']];
        }

        return ['size' => 0];
    }

    /**
     * 获得文件Mime类型
     *
     * @param string $path
     * @return mixed string|null
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getMimetype($path)
    {
        $stat = $this->getMetadata($path);

        if ( $stat && !empty($stat['custom_headers']) && !empty($stat['custom_headers']['Content-Type'])) {
            return ['mimetype' => $stat['custom_headers']['Content-Type']];
        }

        return ['mimetype' => ''];
    }

    /**
     * 获得文件最后修改时间
     *
     * @param string $path
     * @return mixed 时间戳
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getTimestamp($path)
    {
        $stat = $this->getMetadata($path);

        if ( $stat ) {
            return ['timestamp' => $stat['ctime']];
        }

        return ['timestamp' => 0];
    }

    /**
     * 获得文件模式 (未实现)
     *
     * @param string $path
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getVisibility($path)
    {
        return self::VISIBILITY_PUBLIC;
    }

    /**
     * 重命名文件
     *
     * @param string $oldname
     * @param string $newname
     * @return boolean
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function rename($oldname, $newname)
    {
        $pathInfo       =  pathinfo($newname);
        $oldname        =  PathLibrary::normalizerPath($oldname);
        $newname        =  PathLibrary::normalizerPath($newname);
        $oldMetadata    =  $this->getMetadata($oldname);

        // 先递归创建目录
        if ( !empty($pathInfo['dirname']) ) {
            $this->createDir($pathInfo['dirname'], new Config());
        }

        $status =  Response::parseResponse(
            $this->getCos()->moveFile(
                $this->getBucket(),
                $oldname,
                $newname,
                self::FILE_TYPE
            )
        ) != false;

        if ( $status ) {

            //更新 自定义属性字段
            return Response::parseResponse(
                $this->getCos()->updateBase($this->getBucket(), $newname, self::FILE_TYPE, $oldMetadata['authority'], $oldMetadata['custom_headers'])
            ) != false;
        }

        return false;
    }

    /**
     * 复制文件
     *
     * @param string $oldname
     * @param string $newname
     * @return boolean
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function copy($oldname, $newname)
    {
        $pathInfo       = pathinfo($newname);
        $oldname        =  PathLibrary::normalizerPath($oldname);
        $newname        =  PathLibrary::normalizerPath($newname);
        $oldMetadata    =  $this->getMetadata($oldname);

        // 先递归创建目录
        if ( !empty($pathInfo['dirname']) ) {
            $this->createDir($pathInfo['dirname'], new Config());
        }

        $status = Response::parseResponse(
            $this->getCos()->copyFile(
                $this->getBucket(),
                $oldname,
                $newname,
                self::FILE_TYPE
            )
        );

        if ( $status ) {
            //更新 自定义属性字段
            return Response::parseResponse(
                $this->getCos()->updateBase($this->getBucket(), $newname, self::FILE_TYPE, $oldMetadata['authority'], $oldMetadata['custom_headers'])
            ) != false;
        }

        return false;
    }

    /**
     * 删除文件
     *
     * @param $path
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function delete($path)
    {
        return Response::parseResponse(
            $this->getCos()->delFile($this->getBucket(), PathLibrary::normalizerPath($path))
        ) != false ;
    }

    /**
     * 删除文件夹
     *
     * @param string $path
     * @return bool
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function deleteDir($path)
    {
        $path   = PathLibrary::normalizerPath($path, true);
        $stat   = $this->getMetadata($path);

        if ( $stat) {

            // 递归删除文件夹里面的文件
            $files = $this->listContents($path);

            if ( $files ) {
                foreach ( $files as $file ) {
                    $info = $this->getMetadata($file['path']);
                    if ( isset($info['biz_attr']) && $info['biz_attr'] == self::FILE_TYPE ) {
                        $this->delete($file['path']);
                    } else {
                        $this->deleteDir($file['path']);
                    }
                }

                unset($file);
                unset($info);
            }

            return Response::parseResponse(
                $this->getCos()->delFolder($this->getBucket(), $path)
            ) != false;
        }

        return false;
    }

    /**
     * 创建文件夹
     *
     * @param string $dirname
     * @param array $config
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function createDir($dirname, Config $config)
    {
        $path       =  PathLibrary::normalizerPath($dirname, true);
        $pathArr    = explode(DIRECTORY_SEPARATOR, $path);
        $pathArr    = array_filter($pathArr, function($value){
            return !empty($value);
        });

        if ( $pathArr ) {

            $currentPath = '';

            foreach ( $pathArr as $value ) {

                $currentPath .= DIRECTORY_SEPARATOR . $value;

                // 如果已经存在，则跳过
                if ( $this->has( PathLibrary::normalizerPath($currentPath, true) ) ) {
                    continue;
                }

                $status = Response::parseResponse(
                    $this->getCos()->createFolder($this->getBucket(), PathLibrary::normalizerPath($currentPath, true), self::DIR_TYPE)
                );

                if ( $status == false ) {
                    return false;
                }
            }

            unset($value);
            return true;
        }

        return false;
    }

    /**
     * 设置文件模式 (未实现)
     *
     * @param string $path
     * @param string $visibility
     * @return bool
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function setVisibility($path, $visibility)
    {
        return true;
    }

    /**
     * 获取当前文件的URL访问路径
     *
     * @param $file
     * @param int $expire_at
     * @return mixed
     */
    public function getUrl($file, $expire_at = 3600)
    {
        return $this->applyPathPrefix($file);
    }
}