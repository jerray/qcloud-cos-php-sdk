<?php

namespace jerray\QCloudCos;

class Auth
{
	/**
	 * AppID
	 * @var string
	 */
    protected $appId;

	/**
	 * SecretID
	 * @var string
	 */
    protected $secretId;

	/**
	 * SecretKey
	 * @var string
	 */
    protected $secretKey;

    /**
     * 存储组件
     * @var Contracts\Store
     */
    protected $store;

    public function __construct($appId, $secretId, $secretKey, Contracts\Store $store)
    {
        $this->appId = $appId;
        $this->secretId = $secretId;
        $this->secretKey = $secretKey;
        $this->store = $store;
    }

    /**
     * generateSign
     * 生成多次有效签名
     *
     * @param string  $bucket
     * @param int     $expire
     * @param boolean $force  强制生成新的签名
     * @return string
     */
    public function generateSign($bucket, $expire = 604800, $force = false)
    {
        $key = "jerray:qcloud:cos:sign:{$bucket}";
        if (!$force && $this->store->has($key)) {
            return $this->store->get($key);
        }

        $expireAt = time() + $expire;
        $sign = $this->generateBaseSign($bucket, $expireAt);
        $this->store->set($key, $sign, $expire);

        return $sign;
    }

    /**
     * generateOneTimeSign
     * 生成单次有效签名
     *
     * @param string $path
     * @param string $bucket
     * @param boolean $fullPath 是否使用包括appId和bucket在内的完整路径
     * @return string
     */
    public function generateOneTimeSign($path, $bucket, $fullPath = true)
    {
        $path = '/' . ltrim($path, '/');
        if ($fullPath) {
            $path = '/' . $this->appId . '/' . $bucket . $path;
        }
        return $this->generateBaseSign($bucket, 0, $path);
    }

    /**
     * generateBaseSign
     * 生成签名
     *
     * @param string $bucket
     * @param string $expire
     * @param string $fileId
     * @return string
     */
    protected function generateBaseSign($bucket, $expire, $fileId = '')
    {
        $appId = $this->appId;
        $secretId = $this->secretId;
        $current = time();
        $rand = mt_rand();

        $src = "a={$appId}&b={$bucket}&k={$secretId}&e={$expire}&t={$current}&r={$rand}&f={$fileId}";
        $bin = hash_hmac('SHA1', $src, $this->secretKey, true);

        return base64_encode($bin . $src);
    }
}
