<?php

// +----------------------------------------------------------------------
// | date: 2016-11-13
// +----------------------------------------------------------------------
// | FileFunctionTest.php: 文件函数
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

use PHPUnit\Framework\TestCase;
use Yangyifan\Upload\Functions\FileFunction;

class FileFunctionTest extends TestCase
{
    public function testGetFileMimeType()
    {
        $this->assertEquals('text/html', FileFunction::getFileMimeType(base_path('demo.html')));
    }


    public function testGetTmpFile()
    {
        $dirname = FileFunction::getTmpFile();
        $this->assertTrue(is_file($dirname));
        return $dirname;
    }

    /**
     * @depends testGetTmpFile
     */
    public function testDeleteTmpFile($dirname)
    {
        $dirname = FileFunction::deleteTmpFile($dirname);
        $this->assertFalse(is_file($dirname));
    }

}