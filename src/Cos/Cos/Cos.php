<?php

// +----------------------------------------------------------------------
// | date: 2016-12-24
// +----------------------------------------------------------------------
// | CosAdapter.php: COS 上传实现
// +----------------------------------------------------------------------
// | Author: yangyifan <yangyifanphp@gmail.com>
// +----------------------------------------------------------------------

namespace Yangyifan\Upload\Cos\Cos;

class Cos
{
    use Auth, HttpClient, Directory, File;

    const VERSION = 'v4.2.1';
    const API_COSAPI_END_POINT = 'http://region.file.myqcloud.com/files/v2/';
    // Secret id or secret key is not valid.
    const AUTH_SECRET_ID_KEY_ERROR = -1;
    //计算sign签名的时间参数
    const EXPIRED_SECONDS = 180;
    //1M
    const SLICE_SIZE_1M = 1048576;
    //20M 大于20M的文件需要进行分片传输
    const MAX_UNSLICE_FILE_SIZE = 20971520;
    //失败尝试次数
    const MAX_RETRY_TIMES = 3;

    private static $timeout = 60;       // HTTP 请求超时时间
    private static $region = 'gz';      // 默认的区域

    protected $config;


    /**
     * Cos constructor.
     *
     * @description 方法说明
     * @author @author yangyifan <yangyifanphp@gmail.com>
     */
    public function __construct($config)
    {
        $this->config = new \Yangyifan\Config\Config($config);
    }

    /**
     * 设置HTTP请求超时时间
     *
     * @param  int  $timeout  超时时长
     * @return bool
     */
    public static function setTimeout($timeout = 60)
    {
        if (!is_int($timeout) || $timeout < 0) {
            return false;
        }

        self::$timeout = $timeout;
        return true;
    }

    /**
     * 设置区域
     *
     * @param $region
     */
    public static function setRegion($region)
    {
        self::$region = $region;
    }

    /*
     * 内部方法, 规整文件路径
     *
     * @param  string  $path      文件路径
     * @param  string  $isfolder  是否为文件夹
     * @return string
     */
    private static function normalizerPath($path, $isfolder = False)
    {
        if (preg_match('/^\//', $path) == 0) {
            $path = '/' . $path;
        }

        if ($isfolder == True) {
            if (preg_match('/\/$/', $path) == 0) {
                $path = $path . '/';
            }
        }

        $path = preg_replace('#/+#', '/', $path);

        return $path;
    }

    /*
     * 内部公共方法, 发送消息
     *
     * @param  string  $req
     */
    private static function parseRequest($req)
    {
        $rsp = HttpClient::sendRequest($req);

        if ($rsp === false) {
            return array(
                'code'      => Response::COSAPI_NETWORK_ERROR,
                'message'   => 'network error',
            );
        }

        $info = HttpClient::info();
        $ret = json_decode($rsp, true);

        if ($ret === NULL) {
            return array(
                'code'      => Response::COSAPI_NETWORK_ERROR,
                'message'   => $rsp,
                'data'      => array()
            );
        }

        return $ret;
    }

    /**
     * 检查路径是否合法
     *
     * @param $path
     * @return bool
     */
    protected static function isValidPath($path)
    {
        if (strpos($path, '?') !== false) {
            return false;
        }
        if (strpos($path, '*') !== false) {
            return false;
        }
        if (strpos($path, ':') !== false) {
            return false;
        }
        if (strpos($path, '|') !== false) {
            return false;
        }
        if (strpos($path, '\\') !== false) {
            return false;
        }
        if (strpos($path, '<') !== false) {
            return false;
        }
        if (strpos($path, '>') !== false) {
            return false;
        }
        if (strpos($path, '"') !== false) {
            return false;
        }

        return true;
    }

    /*
     * 内部公共方法, 路径编码
     *
     * @param  string  $path 待编码路径
     * @return string
     */
    private static function cosUrlEncode($path)
    {
        return str_replace('%2F', '/',  rawurlencode($path));
    }

    /*
     * 内部公共方法, 构造URL
     *
     * @param  string  $bucket
     * @param  string  $dstPath
     * @return string
     */
    private function generateResUrl($bucket, $dstPath)
    {
        $endPoint = self::API_COSAPI_END_POINT;
        $endPoint = str_replace('region', self::$region, $endPoint);

        return $endPoint . $this->config->get('app_id') . '/' . $bucket . $dstPath;
    }

    /*
     * 内部私有方法
     *
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件/目录路径路径
     *
     * @return array
     */
    private function delBase($bucket, $path)
    {
        if ($path == "/") {
            return [
                'code'      => Response::COSAPI_PARAMS_ERROR,
                'message'   => 'can not delete bucket using api! go to ' .
                    'http://console.qcloud.com/cos to operate bucket'
            ];
        }

        $path       = self::cosUrlEncode($path);
        $url        = self::generateResUrl($bucket, $path);
        $signature  = $this->createNonreusableSignature($bucket, $path);
        $data       = ['op' => 'delete'];
        $data       = json_encode($data);

        $req = [
            'url'       => $url,
            'method'    => 'post',
            'timeout'   => self::$timeout,
            'data'      => $data,
            'header'    => [
                'Authorization: ' . $signature,
                'Content-Type: application/json',
            ],
        ];

        return self::sendRequest($req);
    }

    /*
     * 内部方法
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件/目录路径
     */
    public function statBase($bucket, $path)
    {
        $path       = self::cosUrlEncode($path);
        $expired    = time() + self::EXPIRED_SECONDS;
        $url        = self::generateResUrl($bucket, $path);
        $signature  = $this->createReusableSignature($expired, $bucket);
        $data       = array('op' => 'stat');
        $url        = $url . '?' . http_build_query($data);

        $req = [
            'url'       => $url,
            'method'    => 'get',
            'timeout'   => self::$timeout,
            'header'    => [
                'Authorization: ' . $signature,
            ],
        ];

        return self::sendRequest($req);
    }

    /*
     * 内部公共函数
     * @param  string  $bucket bucket名称
     * @param  string  $path       文件夹路径
     * @param  int     $num        拉取的总数
     * @param  string  $pattern    eListBoth(默认),ListDirOnly,eListFileOnly
     * @param  int     $order      默认正序(=0), 填1为反序,
     * @param  string  $context    在翻页查询时候用到
     */
    private function listBase(
        $bucket, $path, $num = 20, $pattern = 'eListBoth', $order = 0, $context = null)
    {
        $path       = self::cosUrlEncode($path);
        $expired    = time() + self::EXPIRED_SECONDS;
        $url        = $this->generateResUrl($bucket, $path);
        $signature  = $this->createReusableSignature($expired, $bucket);
        $data       = ['op' => 'list',];

        if (self::isPatternValid($pattern) == false) {
            return [
                'code'      => Response::COSAPI_PARAMS_ERROR,
                'message'   => 'parameter pattern invalid',
            ];
        }

        $data['pattern'] = $pattern;

        if ($order != 0 && $order != 1) {
            return [
                'code'      => Response::COSAPI_PARAMS_ERROR,
                'message'   => 'parameter order invalid',
            ];
        }
        $data['order'] = $order;

        if ($num < 0 || $num > 199) {
            return [
                'code'      => Response::COSAPI_PARAMS_ERROR,
                'message'   => 'parameter num invalid, num need less then 200',
            ];
        }
        $data['num'] = $num;

        if (isset($context)) {
            $data['context'] = $context;
        }

        $url = $url . '?' . http_build_query($data);

        $req = [
            'url'       => $url,
            'method'    => 'get',
            'timeout'   => self::$timeout,
            'header'    => [
                'Authorization: ' . $signature,
            ],
        ];

        return self::sendRequest($req);
    }

    /**
     * 判断pattern值是否正确
     * @param  string  $authority
     * @return [type]  bool
     */
    private static function isPatternValid($pattern)
    {
        if ($pattern == 'eListBoth' || $pattern == 'eListDirOnly' || $pattern == 'eListFileOnly') {
            return true;
        }

        return false;
    }

}