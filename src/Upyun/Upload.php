<?php

// +----------------------------------------------------------------------
// | date: 2015-09-09
// +----------------------------------------------------------------------
// | Upload.php: 又拍云上传实现
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------


namespace Yangyifan\Upload\Upyun;

use Yangyifan\Upload\UploadInterface;

class Upload implements UploadInterface
{
    private $upload;

    /**
     * 构造方法
     *
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function __construct()
    {
        //实例化对象
        $this->upload = new UpYun(
            config('upload.upyun.bucketname'),
            config('upload.upyun.username'),
            config('upload.upyun.password')
        );
    }

    /**
     * 写入文件
     *
     * @param $file_name
     * @param $contents
     * @param bool|true $auto_mkdir
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function write($file_name, $contents, $auto_mkdir = true)
    {
        return $this->upload->writeFile($file_name, $contents, $auto_mkdir);
    }

    /**
     * 读取文件
     *
     * @param $file_name
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function read($file_name)
    {
        return $this->upload->readFile($file_name);
    }

    /**
     * 列取空间的文件列表
     *
     * @param int $limit
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function listFiles()
    {
        return $this->upload->listFiles();
    }

    /**
     * 删除文件或者文件夹
     *
     * @param $file_name
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function delete($file_name)
    {
        return $this->upload->delete($file_name);
    }

    /**
     * 获得文件信息
     *
     * @param $file_name
     * @return mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getFileInfo($file_name)
    {
        return $this->upload->getFileInfo($file_name);
    }




}