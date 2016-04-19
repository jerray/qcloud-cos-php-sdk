<?php

namespace jerray\QCloudCos;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;

class RestClient
{
    protected $protocol = 'http://';

    protected $domain = '';

    protected $httpClient;

    public function __construct($options)
    {
        if (isset($options['ssl']) && $options['ssl']) {
            $this->protocol = 'https://';
        }

        if (isset($options['httpClient']) && $options['httpClient']) {
            $this->httpClient = $options['httpClient'];
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
     * @throws Exceptions\ServerException 请求出现500+错误时抛出
     * @throws Exceptions\RequestException 请求出现其他错误时抛出
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
        } catch (ClientException $e) {
            $this->throwRequestException($e, Exceptions\ClientException::class);
        } catch (ServerException $e) {
            $this->throwRequestException($e, Exceptions\ServerException::class);
        } catch (RequestException $e) {
            $this->throwRequestException($e, Exceptions\RequestException::class);
        }

        return $body;
    }

    /**
     * getHttpClient
     *
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new HttpClient();
        }
        return $this->httpClient;
    }

    /**
     * throwRequestException
     *
     * @param \GuzzleHttp\Exception\RequestException $e
     * @param string $className
     * @throws Exceptions\RequestException
     */
    protected function throwRequestException($e, $className)
    {
        $response = $e->getResponse();
        $rawBody = (string) $response->getBody();
        $body = json_decode((string) $rawBody);
        $body = $body === null ? $rawBody : $body;
        $exception = new $className($e->getMessage(), $e->getCode());
        $exception->setBody($body);
        throw $exception;
    }
}
