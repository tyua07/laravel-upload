<?php

// +----------------------------------------------------------------------
// | date: 2015-09-09
// +----------------------------------------------------------------------
// | Upload.php: 上传库
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------


namespace Yangyifan\Upload;;


class Upload
{

    public $drive;//上传引擎

    public function __construct(){

    }

    public function chose($UploadDrive){
        $this->drive = $UploadDrive;
    }

    /**
     * 写入文件
     *
     * @param $file_name
     * @param $contents
     * @param bool|true $auto_mkdir
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function write($file_name, $contents)
    {
        return $this->drive->write($file_name, $contents);
    }

    /**
     * 读取文件
     *
     * @param $file_name
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function read($file_name)
    {
        return $this->drive->read($file_name);
    }

    /**
     * 删除文件或者文件夹
     *
     * @param $file_name
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function delete($file_name)
    {
        return $this->drive->delete($file_name);
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
        return $this->drive->getFileInfo($file_name);
    }
}