##### 安装

* ``` composer require "yangyifan/upload:v0.2"``` 。
* 添加 ``` \Yangyifan\Upload\UploadServiceProvider::class ``` 到您项目 ```config/app.php``` 中。
* 支持七牛，upyun，oss。
* 完成按照官方 ``` Storage  ``` 来扩展。所以不需要修改代码，只需要新增配置文件信息，就可以替换任何一种存储引擎。

###### 开始

*  use Storage

```
use Storage;
```

* 示例代码

```

        $image  = "11/22/33/7125_yangxiansen.jpg";
        $image2 = "111.png";
        $image3 = "2.txt";

        $drive = \Storage::drive('oss');                                                            //选择oss上传引擎

        dump($drive->getMetadata($image2));                                                         //判断文件是否存在
        dump($drive->geturl($image2));                                                              //获得文件的 url
        dump($drive->has($image2));                                                                 //判断文件是否存在
        dump($drive->listContents(''));                                                             //列出文件列表
        dump($drive->getSize($image2));                                                             //获得图片大小
        dump($drive->getMimetype($image2));                                                         //获得图片mime类型
        dump($drive->getTimestamp($image2));                                                        //获得图片上传时间戳
        dump($drive->read($image3));                                                                //获得文件信息
        dump($drive->readStream($image3));                                                          //获得文件信息
        dump($drive->rename($image3, '4.txt/'));                                                    //重命名文件
        dump($drive->copy('4.txt/', '/txt/5.txt'));                                                 //复制文件
        dump($drive->delete('/txt/5.txt'));                                                         //删除文件
        dump ($drive->write("/txt/4.txt", $drive->read("/4.txt")) );                                //上传文件
        dump($drive->write("/test2.txt", "111222"));                                                //上传文件
        dump($drive->deleteDir('txt/'));                                                            //删除文件夹
        dump($drive->createDir('test3/'));                                                          //创建文件夹
        $handle = fopen('/tmp/email.png', 'r');
        dump ($drive->writeStream("/write/test3.png", $handle ) );                                  //上传文件(文件流方式)
        dump ($drive->writeStream("/test6.png", $drive->readStream('/write/test3.png') ) );         //上传文件(文件流方式)

```

>注意：详细使用，可以参考单元测试里面的代码。

###### 配置信息

>注意：请在 ```config\filesystems.php``` 中的 ```disks``` 数组下面加入以下配置。

```

'qiniu' => [
    'driver'        => 'qiniu',
    'domain'        => '',//你的七牛域名
    'access_key'    => '',//AccessKey
    'secret_key'    => '',//SecretKey
    'bucket'        => '',//Bucket名字
    'transport'     => 'http',//如果支持https，请填写https，如果不支持请填写http
],

'upyun' => [
    'driver'        => 'upyun',
    'domain'        => '',//你的upyun域名
    'username'      => '',//UserName
    'password'      => '',//Password
    'bucket'        => '',//Bucket名字
    'timeout'       => 130,//超时时间
    'endpoint'      => null,//线路
    'transport'     => 'http',//如果支持https，请填写https，如果不支持请填写http
],

'oss'	=> [
    'driver'			=> 'oss',
    'accessKeyId'		=> '',
    'accessKeySecret' 	=> '',
    'endpoint'			=> '',
    'isCName'			=> false,
    'securityToken'		=> null,
    'bucket'            => '',
    'timeout'           => '5184000',
    'connectTimeout'    => '10',
    'transport'     	=> 'http',//如果支持https，请填写https，如果不支持请填写http
    'max_keys'          => 1000,//max-keys用于限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000
],

'cos'	=> [
    'driver'			=> 'cos',
    'domain'            => '',      // 你的 COS 域名
    'app_id'            => '',
    'secret_id'         => '',
    'secret_key'        => '',
    'region'            => 'gz',        // 设置COS所在的区域
    'transport'     	=> 'http',      // 如果支持 https，请填写 https，如果不支持请填写 http
    'timeout'           => 60,          // 超时时间
    'bucket'            => '',
],
```

###### 其他

* 如果需要支持其他的上传引擎，请联系我，如果我有空，我会去扩展，希望这个能帮助大家开发，谢谢，有问题pr我，或者邮件联系我，我的邮箱是：yangyifanphp@gmail.com。

###### 更新

###### 2016-12-03
*  新增单元测试

###### 2016-12-03
* 新增腾讯 COS 对象存储（如果可以不要去用，坑有点多。）
* 新增 getUrl 方法

###### 计划
* 新增分片上传
* 继续完善更多场景的单元测试

###### 协议

MIT