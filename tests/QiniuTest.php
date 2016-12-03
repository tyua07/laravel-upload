<?php

// +----------------------------------------------------------------------
// | date: 2016-11-13
// +----------------------------------------------------------------------
// | QiniuTest.php: 测试七牛
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

use PHPUnit\Framework\TestCase;
use Yangyifan\Upload\Qiniu\QiniuAdapter;

class QiniuTest extends TestCase
{
    /**
     * 上传引擎
     *
     * @var QiniuAdapter
     */
    protected static $drive;

    /**
     * 配置信息
     *
     * @var Config
     */
    protected static $config;

    public static function setUpBeforeClass()
    {
        $qiniuConfig    = array_merge(config(require base_path('/config.php'), 'qiniu'), require_once base_path('/qiniuConfig.php'));
        static::$drive  = new QiniuAdapter($qiniuConfig);
        static::$config = new \League\Flysystem\Config($qiniuConfig);

        //上传资源
        if ( !static::$drive->has('demo.txt') ) {
            static::$drive->write('demo.txt', file_get_contents(base_path('/demo.txt')), static::$config);
        }

        if ( !static::$drive->has('demo.jpg') ) {
            static::$drive->write('demo.jpg', file_get_contents(base_path('/demo.jpg')), static::$config);
        }
    }

    public static function tearDownAfterClass()
    {
        self::$config = NULL;
        self::$drive = NULL;
    }

    //测试 Debug
    public function testgetMetadata()
    {
        return static::$drive->getMetadata('demo.txt');
    }

    public function testHas()
    {
        $this->assertEquals(true, static::$drive->has('demo.txt'));
        $this->assertEquals(false, static::$drive->has('demo1.txt'));
    }

    public function testListContents()
    {
        $this->assertNotEmpty(static::$drive->listContents('/'));
    }

    public function testGetSizes()
    {
        $size = static::$drive->getSize('demo.txt')['size'];

        $this->assertEquals($size, 4);
    }

    public function testGetMimetype()
    {
        $mimetype1 = static::$drive->getMimetype('demo.txt')['mimetype'];
        $mimetype2 = static::$drive->getMimetype('demo.jpg')['mimetype'];

        $this->assertEquals($mimetype1, 'text/plain');
        $this->assertEquals($mimetype2, 'image/jpeg');
    }

    public function testGetTimestamp()
    {
        $timestamp1 = static::$drive->getTimestamp('demo.txt')['timestamp'];
        $timestamp2 = static::$drive->getTimestamp('demo.jpg')['timestamp'];

//        $this->assertEquals($timestamp1, '1480663159');
//        $this->assertEquals($timestamp2, '1480663159');
    }

    public function testRead()
    {
        $content = static::$drive->read('demo.txt')['contents'];

        $this->assertEquals($content, 'demo');
    }

    public function testReadStream()
    {
        $resource = static::$drive->readStream('demo.txt')['stream'];

        $content = fgets($resource);
        fclose($resource);

        $this->assertEquals($content, 'demo');
    }

    public function testWrite()
    {
        $this->assertTrue(static::$drive->write('demo.php', '<?php echo 11;?>', static::$config));
    }

    public function testWriteStream()
    {
        $file   = base_path('/demo.html');
        $config = static::$config;
        $handle = fopen($file, 'r');
        $config->set('mimetype', \Yangyifan\Upload\Functions\FileFunction::getFileMimeType($file));
        $this->assertTrue(static::$drive->writeStream('demo.html', $handle, $config));
        fclose($handle);
    }

    public function testRename()
    {
        if (static::$drive->has('demo.php') && !static::$drive->has('demoClass.php')) {
            $this->assertTrue(static::$drive->rename('demo.php', 'demoClass.php'));
        }

    }

    public function testCopy()
    {
        if (!static::$drive->has('app/demoClass1.php') && !static::$drive->has('app/demoClass2.php') && static::$drive->has('demoClass.php')) {
            $this->assertTrue(static::$drive->copy('demoClass.php', 'app/demo1Class.php'));
            $this->assertTrue(static::$drive->copy('demoClass.php', 'app/demo2Class.php'));
        }
    }

    public function testDelete()
    {
        if (static::$drive->has('app/demoClass1.php')) {
            $this->assertTrue(static::$drive->delete('app/demoClass1.php'));
        }
    }

    public function testDeleteDir()
    {
        $this->assertTrue(static::$drive->deleteDir('app'));
    }

    //没有实现该方法
    public function testcreateDir()
    {
        $this->assertTrue(static::$drive->createDir('', static::$config), true);
    }

    /**
     * 获得相关对象
     *
     */
    public function testGetUploadManager()
    {
        static::$drive->getInstance();

    }
}