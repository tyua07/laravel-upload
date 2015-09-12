<?php

// +----------------------------------------------------------------------
// | date: 2015-09-09
// +----------------------------------------------------------------------
// | UploadInterface.php: 上传接口
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

namespace Yangyifan\Upload;

interface UploadInterface
{
    function write($file_name, $contents);//写入文件
    function read($file_name);//读取文件
    function delete($file_name);//删除文件
    function getFileInfo($file_name);//获得文件信息
    function listFiles($path);//获得文件列表
}