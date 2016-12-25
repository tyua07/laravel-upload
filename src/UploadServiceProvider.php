<?php

namespace Yangyifan\Upload;

use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Yangyifan\Upload\Qiniu\QiniuAdapter;
use Yangyifan\Upload\Oss\OssAdapter;
use Yangyifan\Upload\Upyun\UpyunAdapter;
use Yangyifan\Upload\Cos\CosAdapter;

class UploadServiceProvider extends ServiceProvider
{
    /**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
        // 实现七牛文件系统
        $this->extendQiniuStorage();

        // 实现 upyun 文件系统
        $this->extendUpyunStorage();

		// 实现 oss 文件系统
		$this->extendOssStorage();

        // 实现 oss 文件系统
        $this->extendCosStorage();
	}

	/**
	 * Register any application services.
	 *
	 * This service provider is a great spot to register your various container
	 * bindings with the application. As you can see, we are registering our
	 * "Registrar" implementation here. You can add your own bindings too!
	 *
	 * @return void
	 */
	public function register()
	{

	}

    /**
     * 实现七牛文件系统
     *
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    protected function extendQiniuStorage()
    {
        \Storage::extend('qiniu', function($app, $config){
            return new Filesystem(new QiniuAdapter($config), $config);
        });
    }

    /**
     * 实现 upyun 文件系统
     *
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    protected function extendUpyunStorage()
    {
        \Storage::extend('upyun', function($app, $config){
            return new Filesystem(new UpyunAdapter($config), $config);
        });
    }

	/**
	 * 实现 Oss 文件系统
	 *
	 * @author yangyifan <yangyifanphp@gmail.com>
	 */
	protected function extendOssStorage()
	{
		\Storage::extend('oss', function($app, $config){
			return new Filesystem(new OssAdapter($config), $config);
		});
	}

    /**
     * 实现 Cos 文件系统
     *
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    protected function extendCosStorage()
    {
        \Storage::extend('cos', function($app, $config){
            return new Filesystem(new CosAdapter($config), $config);
        });
    }

}
