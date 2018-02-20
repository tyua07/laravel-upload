<?php

// +----------------------------------------------------------------------
// | date: 2016-03-04
// +----------------------------------------------------------------------
// | FileFunction.php: 文件相关方法
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

namespace Yangyifan\Upload\Functions;


class FileFunction
{

    /**
     * 获得文件mime_type
     *
     * @param $file
     * @return bool|mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function getFileMimeType($file)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime_type = finfo_file($finfo, $file);
            finfo_close($finfo);
            return $mime_type;
        }
        return false;
    }

    /**
     * 获得一个临时文件
     *
     * @return string
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function getTmpFile()
    {
        $tmpfname = @tempnam("/tmp", "dir");
        chmod($tmpfname, 0777);
        return $tmpfname;
    }

    /**
     * 删除一个临时文件
     *
     * @param $file_name
     * @return bool
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function deleteTmpFile($file_name)
    {
        return unlink($file_name);
    }
}
