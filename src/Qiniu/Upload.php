<?php

// +----------------------------------------------------------------------
// | date: 2015-09-12
// +----------------------------------------------------------------------
// | Upload.php: 七牛上传实现
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------


namespace Yangyifan\Upload\Qiniu;

use App\Library\Upload\UploadInterface;
use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

class Upload implements UploadInterface
{
    private $auth;
    private $token;
    private $bucket;

    /**
     * 构造方法
     *
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function __construct()
    {
        $this->bucket   = config('upload.qiniu.bucket');

        //实例化对象
        $this->auth     = new Auth(config('upload.qiniu.accessKey'), config('upload.qiniu.secretKey'));
        $this->token    = $this->auth->uploadToken($this->bucket);
    }

    /**
     * 写入文件
     *
     * @param $file_name
     * @param $contents
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function write($file_name, $contents)
    {
        return ( new UploadManager() )->put($this->token, $file_name, $contents);
    }

    /**
     * 读取文件
     *
     * @param $file_name
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function read($file_name)
    {
        return file_get_contents(config('upload.qiniu.url') . '/' .$file_name);
    }

    /**
     * 列取空间的文件列表
     *
     * @param int $limit
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function listFiles($path)
    {
        return ( new BucketManager($this->auth) )->listFiles($this->bucket, $path, $marker = null, $limit = 10000, $delimiter = null);
    }

    /**
     * 删除文件或者文件夹
     *
     * @param $file_name
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function delete($file_name)
    {
        return ( new BucketManager($this->auth) )->delete($this->bucket, $file_name);
    }

    /**
     * 重命名文件
     *
     * @param $oldname
     * @param $newname
     * @return mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function rename($oldname, $newname)
    {
        return ( new BucketManager($this->auth) )->rename($this->bucket, $oldname, $newname);
    }

    /**
     * 复制文件
     *
     * @param $form_name
     * @param $to_name
     * @return mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function copy($from_name, $to_name)
    {
        return ( new BucketManager($this->auth) )->copy($this->bucket, $from_name, $this->bucket, $to_name);
    }

    /**
     * 移动文件到指定空间
     *
     * @param $from_name
     * @param $to_bucket
     * @param $to_name
     * @return mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function move($from_name, $to_bucket, $to_name)
    {
        return ( new BucketManager($this->auth) )->move($this->bucket, $from_name, $to_bucket, $to_name);
    }

    /**
     * 改变文件 Mime类型
     *
     * @param $key
     * @param $mime
     * @return mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function changeMime($key, $mime)
    {
        return ( new BucketManager($this->auth) )->changeMime($this->bucket, $key, $mime);
    }

    /**
     * 从指定URL抓取资源，并将该资源存储到指定空间中
     *
     * @param $url
     * @param $key
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function fetch($url, $key)
    {
        return ( new BucketManager($this->auth) )->fetch($url, $this->bucket, $key);
    }

    /**
     * 获得文件信息
     *
     * @param $file_name
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getFileInfo($file_name)
    {
        return ( new BucketManager($this->auth) )->stat($this->bucket, $file_name);
    }


}