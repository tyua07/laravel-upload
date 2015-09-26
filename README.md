##### 安装

* composer require "yangyifan/upload:dev-master" 
* 添加 UploadServiceProvider 到您项目 config/app.php 中的 providers 部分: Yangyifan\Upload\UploadServiceProvider

###### 开始

* 自动注入 上传组件

```
use Yangyifan\Upload\Upload;
use Yangyifan\Upload\Qiniu\Upload as Qiniu;
```
* 选择上传引擎

```
    /**
     * 构造方法
     *
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function __construct(Upload $upload, Qiniu $qiniu)
    {
        $this->upload           = $upload;
        $this->upload->drive    = $qiniu;//选择上传引擎
    }
```


###### 上传文件

```
$this->upload->write('aa1.txt', 'asasa')
```

###### 读取文件

```
$this->upload->read('aa1.txt')
```

###### 删除文件或者文件夹

```
$this->upload->delete('aa1.txt')
```

###### 获得文件信息

```
$this->upload->getFileInfo('aa1.txt')
```

###### 获得文件列表

```
$this->upload->listFiles()
```
