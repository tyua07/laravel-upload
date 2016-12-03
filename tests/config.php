<?php

return [
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
];


