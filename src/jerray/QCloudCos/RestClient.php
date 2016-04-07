<?php

namespace jerray\QCloudCos;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;

class RestClient
{
    protected $protocol = 'http://';

    protected $domain = '';

    public function __construct($options)
    {
        if ($options['ssl']) {
            $this->protocol = 'https://';
        }

        $this->domain = $options['domain'];
    }

    /**
     * request
     *
     * @param string $method
     * @param string $uri
     * @param string $sign
     * @param array  $userOptions
     * @throws Exceptions\ClientException 请求出现400+错误时抛出
     * @return object
     */
    public function request($method, $uri, $sign, $userOptions = [])
    {
        $httpClient = $this->getHttpClient();
        $options = array_merge([
            'headers' => [
                'Authorization' => $sign,
            ]
        ], $userOptions);

        $url = $this->protocol . $this->domain . '/' . ltrim($uri, '/');
        try {
            $response = $httpClient->request($method, $url, $options);
            $body = json_decode((string) $response->getBody());
            return $body;
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $body = json_decode((string) $response->getBody());
            $exception = new Exceptions\ClientException($e->getMessage(), $e->getCode());
            $exception->setBody($body);
            throw $exception;
        }
    }

    /**
     * getHttpClient
     *
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return new HttpClient();
    }
}
