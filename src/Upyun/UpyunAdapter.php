<?php

// +----------------------------------------------------------------------
// | date: 2015-09-09
// +----------------------------------------------------------------------
// | UpyunAdapter.php: 又拍云上传实现
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

namespace Yangyifan\Upload\Upyun;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use Yangyifan\Upload\Functions\FileFunction;
use Exception;

class UpyunAdapter extends  AbstractAdapter
{
    /**
     * 配置信息
     *
     * @var
     */
    protected $config;

    /**
     * upyun上传对象
     *
     * @var UpYun
     */
    protected $upload;

    /**
     *
     * 文件类型
     *
     */
    const FILE_TYPE_FILE    = 'file';//文件类型为文件
    const FILE_TYPE_FOLDER  = 'folder';//文件类型是文件夹

    /**
     * 构造方法
     *
     * @param array $config   配置信息
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function __construct($config)
    {
        $this->config   = $config;

        //设置路径前缀
        $this->setPathPrefix($this->config['transport'] . '://' . $this->config['domain']);
    }

    /**
     * 获得Upyun上传对象
     *
     * @return UpYun
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    protected function getUpyun()
    {
        if (!$this->upload) {
            $this->upload = new UpYun(
                $this->config['bucket'],//空间名称
                $this->config['username'],//用户名
                $this->config['password'],//密码
                $this->config['endpoint'],//线路
                $this->config['timeout']//超时时间
            );
        }
        return $this->upload;
    }

    /**
     * 获得 Upyun 实例
     *
     * @return UpYun
     */
    public function getInstance()
    {
        return $this->getUpyun();
    }

    /**
     * 重写组合upyun路径
     *
     * @param $path
     * @return string
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    protected function mergePath($path)
    {
        return '/' . trim($path, '/');
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
        return $this->getMetadata($path) != false ? true : false;
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
        //获得一个临时文件
        $tmpfname       = FileFunction::getTmpFile();

        file_put_contents($tmpfname, file_get_contents($this->applyPathPrefix($path)) );

        $handle = fopen($tmpfname, 'r');

        //删除临时文件
        FileFunction::deleteTmpFile($tmpfname);

        return ['stream' => $handle];
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
        return $this->getUpyun()->writeFile($this->mergePath($path), $contents, $auto_mkdir = true);
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
        $status = $this->getUpyun()->writeFile($this->mergePath($path), $resource, true);

        return $status;
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
        try{
            //组合目录
            $directory  = $this->mergePath($directory);

            $file_list = $this->getUpyun()->getList($directory);

            if (is_array($file_list) && count($file_list) > 0 ) {
                foreach ($file_list as &$file) {
                    $file['path']   = ltrim($directory, '/') . DIRECTORY_SEPARATOR . $file['name'];
                }
            }
            return $file_list;
        }catch (Exception $e){

        }
        return [];
    }

    /**
     * 获取资源的元信息，但不返回文件内容
     *
     * @param $path
     * @return mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function getMetadata($path)
    {
        try {
            $file_info = $this->getUpyun()->getFileInfo($path);
            if ( !empty($file_info) ) {
                return $file_info;
            }
        }catch (Exception $exception){
            //调试信息
            //echo $exception->getMessage();
        }
        return false;
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
        $file_info = $this->getMetadata($path);
        return $file_info != false && $file_info['x-upyun-file-size'] > 0 ? [ 'size' => $file_info['x-upyun-file-size'] ] : false;
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
        //创建一个临时文件
        $tmp_file = FileFunction::getTmpFile();

        file_put_contents($tmp_file, $this->readStream($path)['stream']);

        $mime_type = FileFunction::getFileMimeType($tmp_file);

        //删除临时文件

        FileFunction::deleteTmpFile($tmp_file);

        return ['mimetype' => $mime_type];
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
        $file_info = $this->getMetadata($path);
        return $file_info != false && !empty($file_info['x-upyun-file-date']) ? ['timestamp' => $file_info['x-upyun-file-date'] ] : false;
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
        $newpath = $this->mergePath($newpath);

        $this->writeStream($newpath, $this->readStream($path)['stream'], new Config() );

        $this->delete($path);

        return true;
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
        $this->writeStream($this->mergePath($newpath), $this->readStream($this->mergePath($path))['stream'], new Config() );

        return true;
    }

    /**
     * 删除文件或者文件夹
     *
     * @param $path
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function delete($path)
    {
        return $this->getUpyun()->delete($this->mergePath($path));
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
        if ( $this->has($path) ) {
            //递归删除全部子文件
            $this->recursiveDeleteDir($path);
            return true;
        }
        return false;
    }

    /**
     * 递归删除全部文件夹
     *
     * @param $path
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    protected function recursiveDeleteDir($path)
    {
        $path = $this->mergePath($path);
        $file_list = $this->listContents($path);

        if ( is_array($file_list) && count($file_list) > 0 ) {
            foreach ($file_list as $file) {
                //如果是文件，则把文件删除
                if ($file['type'] == self::FILE_TYPE_FILE) {
                    $this->delete($path . $this->pathSeparator . $file['name']);
                } else {
                    $this->recursiveDeleteDir($path . $this->pathSeparator . $file['name']);
                }
            }
        }
        $this->getUpyun()->rmDir($path);
    }

    /**
     * 创建文件夹
     *
     * @param string $dirname
     * @param array $config
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public function createDir($dirname, Config $config)
    {
        $this->getUpyun()->makeDir($this->mergePath($dirname));
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