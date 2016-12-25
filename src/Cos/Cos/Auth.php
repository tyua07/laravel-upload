<?php

namespace Yangyifan\Upload\Cos\Cos;

trait Auth
{
    /**
     * 创建多次签名
     *
     * @param int $expiration           时间戳
     * @param string $bucket            bucket 名称
     * @param null $filepath            文件路径
     * @return string
     */
    protected function createReusableSignature($expiration, $bucket, $filepath = null)
    {
        if ( empty($filepath)) {
            return $this->createSignature($expiration, $bucket, null);
        } else {
            if (preg_match('/^\//', $filepath) == 0) {
                $filepath = '/' . $filepath;
            }

            return $this->createSignature($expiration, $bucket, $filepath);
        }
    }

    /**
     * 创建单次签名
     *
     * @param string $bucket   bucket 名称
     * @param string $filepath  文件路径
     * @return string
     */
    protected function createNonreusableSignature($bucket, $filepath)
    {
        if (preg_match('/^\//', $filepath) == 0) {
            $filepath = '/' . $filepath;
        }

        $fileId = '/' . $this->config->get('app_id') . '/' . $bucket . $filepath;

        return $this->createSignature(0, $bucket, $fileId);
    }

    /**
     * 创建签名
     *
     * @param int $expiration   时间戳
     * @param string $bucket    bucket 名称
     * @param string $fileId    文件路径
     * @return string
     */
    private function createSignature($expiration, $bucket, $fileId)
    {
        $appId      = $this->config->get('app_id');
        $secretId   = $this->config->get('secret_id');
        $secretKey  = $this->config->get('secret_key');
        $now        = time();
        $random     = rand();
        $plainText  = "a=$appId&k=$secretId&e=$expiration&t=$now&r=$random&f=$fileId&b=$bucket";
        $bin        = hash_hmac('SHA1', $plainText, $secretKey, true);
        $bin        = $bin.$plainText;
        $signature  = base64_encode($bin);

        return $signature;
    }
}
