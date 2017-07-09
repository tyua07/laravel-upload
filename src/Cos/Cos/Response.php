<?php

// +----------------------------------------------------------------------
// | date: 2016-12-24
// +----------------------------------------------------------------------
// | Response.php: COS 响应相关
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

namespace Yangyifan\Upload\Cos\Cos;

class Response
{
    /**
     * 响应的 code 相关
     */
    const COSAPI_SUCCESS         = 0;
    const COSAPI_PARAMS_ERROR    = -1;
    const COSAPI_NETWORK_ERROR   = -2;
    const COSAPI_INTEGRITY_ERROR = -3;

    /**
     * 解析响应
     *
     * @param $response
     * @return bool|mixed
     */
    public static function parseResponse($response)
    {
        $response = is_array($response) ? $response : json_decode($response, true);

        if ( $response && $response['code'] == self::COSAPI_SUCCESS) {
            return $response;
        }

        return false;
    }
}