<?php

namespace jerray\QCloudCos\Tests;

use Faker\Factory as Faker;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use jerray\QCloudCos\RestClient;
use TestCase;

class RestClientTest extends TestCase
{
    public function setUp()
    {
        $this->faker = Faker::create();
        $this->container = [];
    }

    public function testAuthHeader()
    {
        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $restClient = $this->genRestClient($httpClient);

        $uri = $this->genFakeUri();
        $sign = $this->faker->md5;
        $restClient->request('POST', $uri, $sign);

        $transaction = $this->container[0];
        $this->assertEquals($sign, $transaction['request']->getHeaderLine('Authorization'));
    }

    public function testSslOption()
    {
        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $restClient = $this->genRestClient($httpClient, ['ssl' => true]);

        $uri = $this->genFakeUri();
        $sign = $this->faker->md5;
        $restClient->request('POST', $uri, $sign);

        $transaction = $this->container[0];
        $this->assertRegExp('/^https:\/\//', (string)$transaction['request']->getUri());
    }

    /**
     * @expectedException \jerray\QCloudCos\Exceptions\ClientException
     */
    public function testClientException()
    {
        $mock = new MockHandler([new Response(400)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $restClient = $this->genRestClient($httpClient);

        $uri = $this->genFakeUri();
        $sign = $this->faker->md5;
        $restClient->request('POST', $uri, $sign);
    }

    protected function genRestClient($httpClient, $options = [])
    {
        $domain = $this->faker->domainName;
        return new RestClient(array_merge([
            'domain' => $domain,
            'httpClient' => $httpClient,
        ], $options));
    }

    protected function genFakeUri()
    {
        return preg_replace('/https?:\/\/[^\/]+/', '', $this->faker->url);
    }

    protected function setupHttpTestClient($mock)
    {
        $history = Middleware::history($this->container);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        return new HttpClient(['handler' => $stack]);
    }
}
