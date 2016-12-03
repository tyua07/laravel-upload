<?php

// +----------------------------------------------------------------------
// | date: 2016-12-02
// +----------------------------------------------------------------------
// | helper.php: 单元测试的帮组函数
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

if ( !function_exists('env') ) {
    function env($name, $default = ''){
        return isset($_ENV[$name]) ? $_ENV[$name] : $default;
    }
}

if ( !function_exists('base_path') ) {
    function base_path($path = null) {
        return !is_null($path) ? __DIR__ . '/' . trim($path, '//') : __DIR__  ;
    }
}

if ( !function_exists('dd') ) {
    function dd($data) {
        var_dump($data);die;
    }
}

if ( !function_exists('config') ) {
    function config($config, $name)
    {
        if (is_null($name)) {
            return $config;
        }

        if (isset($config[$name])) {
            return $config[$name];
        }

        foreach (explode('.', $name) as $segment) {
            if (is_array($config) && array_key_exists($segment, $config)) {
                $config = $config[$segment];
            }

        }

        return $config;
    }
}

