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
use jerray\QCloudCos\QCloudCos;
use jerray\QCloudCos\LocalStore;
use TestCase;

class QCloudCosTest extends TestCase
{
    public function setUp()
    {
        $this->faker = Faker::create();
        $this->container = [];

        $this->appId = $this->faker->randomNumber;
        $this->bucket = $this->faker->userName;
        $this->secretId = $this->faker->md5;
        $this->secretKey = $this->faker->md5;
        $this->fileid = $this->faker->md5 . '.' . $this->faker->fileExtension;
        $this->folder = $this->faker->md5;
        $this->endPoint = 'files/v1';
    }

    public function testUpload()
    {
        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);
	}

    public function testDownloadFile()
    {
    }

    public function testUpdateFile()
    {
        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $biz = $this->faker->md5;
        $cos->updateFile($this->bucket, $this->fileid, $biz);

        $request = $this->container[0]['request'];
        $this->assertRequestMethodEquals("POST", $request);
        $this->assertRequestUriEquals("/{$this->endPoint}/{$this->appId}/{$this->bucket}/{$this->fileid}", $request);
        $this->assertRequestJsonBody(['op' => 'update', 'biz_attr' => $biz], $request);
    }

    public function testQueryFile()
    {
        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $cos->queryFile($this->bucket, $this->fileid);

        $request = $this->container[0]['request'];
        $this->assertRequestMethodEquals("GET", $request);
        $this->assertRequestUriEquals("/{$this->endPoint}/{$this->appId}/{$this->bucket}/{$this->fileid}", $request);
        $this->assertRequestQuery(['op' => 'stat'], $request);
    }

    public function testDeleteFile()
    {
        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $cos->deleteFile($this->bucket, $this->fileid);

        $request = $this->container[0]['request'];
        $this->assertRequestMethodEquals("POST", $request);
        $this->assertRequestUriEquals("/{$this->endPoint}/{$this->appId}/{$this->bucket}/{$this->fileid}", $request);
        $this->assertRequestJsonBody(['op' => 'delete'], $request);
    }

    public function testCreateFoler()
    {
        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $biz = $this->faker->md5;
        $cos->createFolder($this->bucket, $this->folder, $biz);

        $request = $this->container[0]['request'];
        $this->assertRequestMethodEquals("POST", $request);
        $this->assertRequestUriEquals("/{$this->endPoint}/{$this->appId}/{$this->bucket}/{$this->folder}/", $request);
        $this->assertRequestJsonBody(['op' => 'create', 'biz_attr' => $biz], $request);
    }

    public function testListFolder()
    {
        $mock = new MockHandler([
            new Response(200),
            new Response(200),
            new Response(200),
            new Response(200),
        ]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $limit = $this->faker->randomNumber;
        $cos->listFolder($this->bucket, $this->folder . '/', $limit);

        $request = $this->container[0]['request'];
        $this->assertRequestMethodEquals("GET", $request);
        $this->assertRequestUriEquals("/{$this->endPoint}/{$this->appId}/{$this->bucket}/{$this->folder}/", $request);
        $this->assertRequestQuery([
            'op' => 'list',
            'num' => $limit,
            'pattern' => 'eListBoth',
            'context' => '',
            'order' => '0',
        ], $request);

        $cos->listFolder($this->bucket, $this->folder . '/', $limit, 'file');
        $request = $this->container[1]['request'];
        $this->assertRequestQuery([
            'op' => 'list',
            'num' => $limit,
            'pattern' => 'eListFileOnly',
            'context' => '',
            'order' => '0',
        ], $request);

        $cos->listFolder($this->bucket, $this->folder . '/', $limit, 'folder');
        $request = $this->container[2]['request'];
        $this->assertRequestQuery([
            'op' => 'list',
            'num' => $limit,
            'pattern' => 'eListDirOnly',
            'context' => '',
            'order' => '0',
        ], $request);

        $context = $this->faker->md5;
        $cos->listFolder($this->bucket, $this->folder . '/', $limit, 'both', $context, 'prev');
        $request = $this->container[3]['request'];
        $this->assertRequestQuery([
            'op' => 'list',
            'num' => $limit,
            'pattern' => 'eListBoth',
            'context' => $context,
            'order' => '1',
        ], $request);
    }

    public function testUpdateFolder()
    {
        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $biz = $this->faker->md5;
        $cos->updateFolder($this->bucket, $this->folder, $biz);

        $request = $this->container[0]['request'];
        $this->assertRequestMethodEquals("POST", $request);
        $this->assertRequestUriEquals("/{$this->endPoint}/{$this->appId}/{$this->bucket}/{$this->folder}/", $request);
        $this->assertRequestJsonBody(['op' => 'update', 'biz_attr' => $biz], $request);
    }

    public function  testQueryFolder()
    {
        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $cos->queryFolder($this->bucket, $this->folder);

        $request = $this->container[0]['request'];
        $this->assertRequestMethodEquals("GET", $request);
        $this->assertRequestUriEquals("/{$this->endPoint}/{$this->appId}/{$this->bucket}/{$this->folder}/", $request);
        $this->assertRequestQuery(['op' => 'stat'], $request);
    }

    public function testDeleteFolder()
    {
        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $cos->deleteFolder($this->bucket, $this->folder);

        $request = $this->container[0]['request'];
        $this->assertRequestMethodEquals("POST", $request);
        $this->assertRequestUriEquals("/{$this->endPoint}/{$this->appId}/{$this->bucket}/{$this->folder}/", $request);
        $this->assertRequestJsonBody(['op' => 'delete'], $request);
    }

    protected function setupHttpTestClient($mock)
    {
        $history = Middleware::history($this->container);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        return new HttpClient(['handler' => $stack]);
    }

    protected function genCosClient($httpClient, $options = [])
    {
        $this->client = new QCloudCos(array_merge([
            'appId' => $this->appId,
            'secretId' => $this->secretId,
            'secretKey' => $this->secretKey,
            'httpClient' => $httpClient,
        ], $options));
        return $this->client;
    }

    protected function assertRequestMethodEquals($method, $request, $comment = '')
    {
        $this->assertEquals($method, $request->getMethod(), $comment);
    }

    protected function assertRequestUriEquals($uri, $request, $comment = '')
    {
        $this->assertEquals($uri, $request->getUri()->getPath(), $comment);
    }

    protected function assertRequestJsonBody($body, $request, $comment = '')
    {
        $this->assertJsonStringEqualsJsonString(json_encode($body), (string)$request->getBody(), $comment);
    }

    protected function assertRequestQuery($query, $request, $comment = '')
    {
        parse_str($request->getUri()->getQuery(), $requestQuery);
        $this->assertEquals($query, $requestQuery, $comment);
    }
}

