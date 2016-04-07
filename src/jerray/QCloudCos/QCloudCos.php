<?php

namespace jerray\QCloudCos;

class QCloudCos
{
    /**
     * @var string
     */
    const DOMAIN = 'web.file.myqcloud.com';

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
     * Api接口路径
     * @var array
     */
    protected $apiEndPoints = [
        'image' => '/photos/v1',
        'video' => '/videos/v1',
        'file' => '/files/v1',
    ];

    /**
     * 签名组件
     * @var Auth
     */
    protected $auth;

    /**
     * 存储组件 用于存储多次有效签名
     * @var Contracts\Store
     */
    protected $store;

    /**
     * RestClient
     * @var RestClient
     */
    protected $restClient;

    /**
     * __construct
     *
     * @param array $options
     *      + appId              腾讯云提供的AppID
     *      + secretId           腾讯云生成的SecretID
     *      + secretKey          腾讯云生成的SecretKey
     *      + store (optional)   用于存储可多次使用的签名的存储对象
     *      + ssl (optional)     是否开启HTTPS 默认false
     *      + domain (optional)  腾讯云接口域名（如果使用反向代理访问腾讯云，可以设置该选项）
     *
     * @throws Exceptions\InvalidStoreInstance $options['store']不是合法的Contracts\Store对象
     */
    public function __construct($options = [])
    {
        $this->appId = $options['appId'];
        $this->secretId = $options['secretId'];
        $this->secretKey = $options['secretKey'];

        if (isset($options['store'])) {
            $store = $options['store'];
            if (!($store instanceof Contracts\Store)) {
                throw new Exceptions\InvalidStoreInstance("Value of option `store` is not a valid "
                    . Contracts\Store::class . " Object");
            }
            $this->store = $store;
        } else {
            $this->store = new LocalStore();
        }

        $this->auth = new Auth($this->appId, $this->secretId, $this->secretKey, $this->store);
        $this->restClient = new RestClient([
            'ssl' => isset($options['ssl']) ? $options['ssl'] : false,
            'domain' => isset($options['domain']) ? $options['domain'] : self::DOMAIN,
        ]);
    }

    /**
     * getAuth
     * 获取签名组件实例
     *
     * @return Auth
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * getStore
     * 获取存储组件实例
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * upload
     * 上传文件
     *
     * @param string $src    本地文件路径
     * @param string $bucket
     * @param string $dest   远程文件路径
     * @param string $biz    文件属性信息
     * @throws Exceptions\FileNotFoundException $src不存在时
     * @throws Exceptions\ClientException 请求失败时
     * @return object
     */
    public function upload($src, $bucket, $dest, $biz = null)
    {
        $src = realpath($src);
        if (!file_exists($src)) {
            throw new Exceptions\FileNotFoundException("File {$src} not found", 404);
        }

        $url = $this->buildUrl('file', $bucket, $dest);
        $sha1 = hash_file('sha1', $src);
        $sign = $this->auth->generateSign($bucket);

        return $this->restClient->request('POST', $url, $sign, [
            'multipart' => [[
                'name' => 'op',
                'contents' => 'upload',
            ], [
                'name' => 'sha',
                'contents' => $sha1,
            ], [
                'name' => 'biz_attr',
                'contents' => isset($biz) ? $biz : '',
            ], [
                'name' => 'filecontent',
                'contents' => fopen($src, 'r'),
            ]]
        ]);
    }

    /**
     * queryFile
     * 查询文件
     *
     * @param string $bucket
     * @param string $src  远程文件名
     * @param string $biz  文件属性信息
     * @throws Exceptions\ClientException 请求失败时
     * @return object
     */
    public function updateFile($bucket, $src, $biz = null)
    {
        $url = $this->buildUrl('file', $bucket, $src);
        $sign = $this->auth->generateOneTimeSign($src, $bucket);

        return $this->restClient->request('POST', $url, $sign, [
            'json' => [
                'op' => 'update',
                'biz_attr' => isset($biz) ? $biz : '',
            ]
        ]);
    }

    /**
     * queryFile
     * 查询文件
     *
     * @param string $bucket
     * @param string $src  远程文件名
     * @throws Exceptions\ClientException 请求失败时
     * @return object
     */
    public function queryFile($bucket, $src)
    {
        $url = $this->buildUrl('file', $bucket, $src);
        $sign = $this->auth->generateSign($bucket);

        return $this->restClient->request('GET', $url, $sign, [
            'query' => ['op' => 'stat']
        ]);
    }

    /**
     * deleteFile
     * 删除文件
     *
     * @param string $bucket
     * @param string $src  远程文件名
     * @throws Exceptions\ClientException 请求失败时
     * @return object
     */
    public function deleteFile($bucket, $src)
    {
        $url = $this->buildUrl('file', $bucket, $src);
        $sign = $this->auth->generateOneTimeSign($src, $bucket);

        return $this->restClient->request('POST', $url, $sign, [
            'json' => [
                'op' => 'delete',
            ]
        ]);
    }

    /**
     * downloadFile
     * 下载文件
     *
     * @param string $bucket
     * @param string $src  远程文件名
     * @param string $dest (optional) 本地文件路径
     * @throws \GuzzleHttp\Exception\ClientException 请求失败时
     * @return string|boolean
     */
    public function downloadFile($bucket, $src, $dest = null)
    {
        $file = $this->queryFile($bucket, $src);
        $sign = $this->auth->generateOneTimeSign($src, $bucket, false);

        $url = $file->data->access_url . '?sign=' . $sign;
        if ($dest) {
            $this->restClient->getHttpClient()->request('GET', $url, [
                'sink' => $dest
            ]);
            return true;
        } else {
            return $url;
        }
    }

    protected function buildUrl($type, $bucket, $resource)
    {
        return $this->apiEndPoints[$type] . '/' .
            $this->appId . '/' .
            $bucket . '/' .
            str_replace('%2F', '/', rawurlencode(ltrim($resource, '/')));
    }
}
