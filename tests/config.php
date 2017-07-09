<?php

return [
    'qiniu' => [
        'driver'        => 'qiniu',
        'domain'        => '',      // 你的七牛域名
        'access_key'    => '',      // AccessKey
        'secret_key'    => '',      // SecretKey
        'bucket'        => '',      // Bucket 名字
        'transport'     => 'http',  // 如果支持 https，请填写 https，如果不支持请填写 http
    ],

    'upyun' => [
        'driver'        => 'upyun',
        'domain'        => '',      // 你的 upyun 域名
        'username'      => '',      // UserName
        'password'      => '',      // Password
        'bucket'        => '',      // Bucket名字
        'timeout'       => 130,     // 超时时间
        'endpoint'      => null,    // 线路
        'transport'     => 'http',  // 如果支持 https，请填写 https，如果不支持请填写 http
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
        'transport'     	=> 'http',  // 如果支持 https，请填写 https，如果不支持请填写 http
        'max_keys'          => 1000,    // max-keys 用于限定此次返回 object 的最大数，如果不设定，默认为100，max-keys 取值不能大于 1000
    ],

    'cos'	=> [
        'driver'			=> 'cos',
        'domain'            => '',      // 你的 COS 域名
        'app_id'            => '',
        'secret_id'         => '',
        'secret_key'        => '',
        'region'            => 'gz',        // 设置COS所在的区域
        'transport'     	=> 'http',      // 如果支持 https，请填写 https，如果不支持请填写 http
        'sign_type'         => 'once',      // 签名是否允许多次   [once => '单次', 'repeatedly' => '多次']
        'timeout'           => 60,          // 超时时间
        'bucket'            => '',
    ],
];


