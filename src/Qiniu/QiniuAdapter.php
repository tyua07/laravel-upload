<?php

// +----------------------------------------------------------------------
// | date: 2015-09-12
// +----------------------------------------------------------------------
// | Upload.php: 七牛上传实现
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

namespace Yangyifan\Upload\Qiniu;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use Qiniu\Storage\ResumeUploader;
use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Config AS QiniuConfig;
use Symfony\Component\Finder\SplFileInfo;
use InvalidArgumentException;
use Yangyifan\Upload\Functions\FileFunction;

class QiniuAdapter extends AbstractAdapter
{
    /**
     * Auth
     *
     * @var Auth
     */
    protected $auth;

    /**
     * token
     *
     * @var string
     */
    protected $token;

    /**
     * bucket
     *
     * @var
     */
    protected $bucket;

    /**
     * 七牛空间管理对象
     *
     * @var
     */
    protected $bucketManager;

    /**
     * 上传对象
     *
     * @var
     */
    protected $uploadManager;

    /**
     * 二进制流上传对象
     *
     * @var
     */
    protected $resumeUploader;

    /**
     * 配置信息
     *
     * @var array
     */
    protected $config;

    /**
     * 构造方法
     *
     * @param array $config 配置信息
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function __construct($config)
    {
        $this->config   = $config;
        $this->bucket   = $this->config['bucket'];
        $this->auth     = new Auth($this->config['access_key'], $this->config['secret_key']);
        $this->token    = $this->auth->uploadToken($this->bucket);

        //设置路径前缀
        $this->setPathPrefix($this->config['transport'] . '://' . $this->config['domain']);
    }

    /**
     * 获得七牛空间管理对象
     *
     * @return BucketManager
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    protected function getBucketManager()
    {
        if (!$this->bucketManager) {
            $this->bucketManager = new BucketManager($this->auth);
        }
        return $this->bucketManager;
    }

    /**
     * 获得七牛上传对象
     *
     * @return UploadManager
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    protected function getUploadManager()
    {
        if (!$this->uploadManager) {
            $this->uploadManager = new UploadManager();
        }
        return $this->uploadManager;
    }

    /**
     * 获得二进制流上传对象
     *
     * @param $key          上传文件名
     * @param $inputStream  上传二进制流
     * @param $config       配置信息
     * @param $params       自定义变量
     * @return ResumeUploader
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    protected function getResumeUpload($key, $inputStream, Config $config, $params = null)
    {
        if (!$this->resumeUploader) {
            if (!$config->has('file_path')) {
                throw new InvalidArgumentException("请配置 file_path 选项，此选项表示需要上传的文件的路径");
            }

            $file_path = $config->get('file_path');

            if ( $file_path ) {
                $file_info = new \SplFileInfo($file_path);

                if ($file_info->isFile() == true) {
                    $this->resumeUploader = new ResumeUploader( $this->token, $key, $inputStream, $file_info->getSize(), $params, FileFunction::getFileMimeType($file_path), (new QiniuConfig()) );
                }
            }

            throw new InvalidArgumentException("{$file_path} 不是一个文件");
        }
        return $this->resumeUploader;
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
        $file_stat = $this->getMetadata($path);
        return !empty($file_stat) ? true : false;
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
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function write($path, $contents, Config $config)
    {
        list(, $error) = $this->getUploadManager()->put($this->token, $path, $contents);

        if ($error) {
            return false;
        }
        return true;
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
        list(, $error) = $this->getResumeUpload($path, $resource, $config)->upload();

        return $error ? false : true;
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
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function listContents($directory = '', $recursive = false)
    {
        list($file_list, $marker, $error) = $this->getBucketManager()->listFiles($this->bucket, $directory);

        if (!$error) {
            foreach ($file_list as &$file) {
                $file['path']   = $file['key'];
                $file['marker'] = $marker;//用于下次请求的标识符
            }
            return $file_list;
        }
        return false;
    }

    /**
     * 获取资源的元信息，但不返回文件内容
     *
     * @param $path
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getMetadata($path)
    {
        list($info, $error) = $this->getBucketManager()->stat($this->bucket, $path);

        if ($error) {
            return false;
        }
        return $info;
    }

    /**
     * 获得文件大小
     *
     * @param string $path
     * @return int
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getSize($path)
    {
        list($fsize, , , ) = array_values($this->getMetadata($path));
        return $fsize > 0 ? [ 'size' => $fsize ] : false;
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
        list(, , $mimeType,) = array_values($this->getMetadata($path));
        return !empty($mimeType) ? ['mimetype' => $mimeType ] : false;
    }

    /**
     * 获得文件最后修改时间
     *
     * @param string $path
     * @return int 时间戳
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getTimestamp($path)
    {
        list(, , , $timestamp) = array_values($this->getMetadata($path));
        return !empty($timestamp) ? ['timestamp' => $timestamp ] : false;
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
     * @param $oldname
     * @param $newname
     * @return boolean
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function rename($path, $newpath)
    {
        return $this->getBucketManager()->rename($this->bucket, $path, $newpath) == null ? true : false;
    }

    /**
     * 复制文件
     *
     * @param $path
     * @param $newpath
     * @return boolean
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function copy($path, $newpath)
    {
        return $this->getBucketManager()->copy($this->bucket, $path, $this->bucket, $newpath) == null ? true : false;
    }

    /**
     * 删除文件或者文件夹
     *
     * @param $path
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function delete($path)
    {
        return $this->getBucketManager()->delete($this->bucket, $path);
    }

    /**
     * 删除文件夹
     *
     * @param string $path
     * @return mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function deleteDir($path)
    {
        list($file_list, , $error) = $this->getBucketManager()->listFiles($this->bucket, $path);

        if (!$error) {
            foreach ( $file_list as $file) {
                $this->delete($file['key']);
            }
        }
        return true;
    }

    /**
     * 创建文件夹(因为七牛没有文件夹的概念，所以此方法没有实现)
     *
     * @param string $dirname
     * @param array $config
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function createDir($dirname, Config $config)
    {
        return true;
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
}