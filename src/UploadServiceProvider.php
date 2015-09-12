<?php

namespace Yangyifan\Upload;

use Illuminate\Support\ServiceProvider;

class UploadServiceProvider extends ServiceProvider
{

	/**
	 * 指定是否延缓提供者加载。
	 *
	 * @var bool
	 */
	protected $defer = true;


	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		//
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
		$this->app->singleton('Yangyifan\Upload', function(){
			return new Upload();
		});
	}

}
