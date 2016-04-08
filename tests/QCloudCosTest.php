<?php

namespace jerray\QCloudCos\Tests;

use Exception;
use Faker\Factory as Faker;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use jerray\QCloudCos\Auth;
use jerray\QCloudCos\LocalStore;
use jerray\QCloudCos\QCloudCos;
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

        $this->tmpDir = dirname(__FILE__) . '/tmp';
    }

    public function tearDown()
    {
        array_map('unlink', glob($this->tmpDir . '/*.tmp'));
    }

    public function testGetAuth()
    {
        $cos = $this->genCosClient(null);
        $this->assertInstanceOf(Auth::class, $cos->getAuth());
    }

    public function testGetStore()
    {
        $cos = $this->genCosClient(null);
        $this->assertInstanceOf(LocalStore::class, $cos->getStore());
    }

    public function testExternalStore()
    {
        $store = new LocalStore();
        $cos = $this->genCosClient(null, [
            'store' => $store
        ]);
        $this->assertEquals($store, $cos->getStore());
    }

    /**
     * @expectedException \jerray\QCloudCos\Exceptions\InvalidStoreInstance
     */
    public function testInvalidExternalStore()
    {
        $this->genCosClient(null, ['store' => true]);
    }

    public function testUpload()
    {
        $file = $this->genTempFile();
        $sha1 = hash_file('sha1', $file);

        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $cos->upload($file, $this->bucket, $this->fileid);
        $request = $this->container[0]['request'];

        $contentType = $request->getHeaderLine('content-type');
        $this->assertContains('multipart/form-data', $contentType);

        $this->assertRequestMultipartHasField($request, 'op', 'upload');
        $this->assertRequestMultipartHasField($request, 'sha', $sha1);
        $this->assertRequestMultipartHasField($request, 'biz_attr', '');
        $this->assertRequestMultipartHasField($request, 'filecontent', file_get_contents($file));
	}

    /**
     * @expectedException \jerray\QCloudCos\Exceptions\FileNotFoundException
     */
    public function testUploadMissingFile()
    {
        $src = $this->genTempFilePath();

        $mock = new MockHandler([new Response(200)]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $cos->upload($src, $this->bucket, $this->fileid);

        $request = $this->container[0]['request'];
	}

    public function testDownloadFile()
    {
        $file = $this->genTempFile();
        $sha1 = hash_file('sha1', $file);

        $dest = $this->genTempFilePath();

        $resourceUrl = $this->faker->url;
        $queryFileResponseBody = [
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'access_url' => $resourceUrl,
                'sha' => $sha1,
            ],
        ];
        $mock = new MockHandler([
            new Response(200, [], json_encode($queryFileResponseBody)),

            new Response(200, [], json_encode($queryFileResponseBody)),
            new Response(200, [], file_get_contents($file)),

            new Response(200, [], json_encode($queryFileResponseBody)),
        ]);
        $httpClient = $this->setupHttpTestClient($mock);
        $cos = $this->genCosClient($httpClient);

        $url = $cos->downloadFile($this->bucket, $this->fileid);
        $this->assertContains($resourceUrl . '?sign=', $url);

        $result = $cos->downloadFile($this->bucket, $this->fileid, $dest);
        $this->assertTrue($result);
        $this->assertFileExists($dest);
        $this->assertEquals($sha1, hash_file('sha1', $dest));

        // 重复下载已缓存的文件 不再发起文件下载请求
        $result = $cos->downloadFile($this->bucket, $this->fileid, $dest);
        $this->assertTrue($result);
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

    protected function genTempFilePath()
    {
        return $this->tmpDir . '/' . $this->faker->uuid . '.tmp';
    }

    protected function genTempFile()
    {
        $filename = $this->genTempFilePath();
        file_put_contents($filename, $this->faker->sha256);
        return $filename;
    }

    protected function parseMultipartBody($body)
    {
        $boundary = $body->getBoundary();
        $blocks = preg_split("/-+{$boundary}/", (string)$body);
        array_pop($blocks);

        $data = [];
        foreach ($blocks as $block) {
            if (empty($block)) {
                continue;
            }

            $parts = explode("\r\n", $block);
            $disposition = $parts[1];
            $value = $parts[count($parts) - 2];
            $fields = [];

            $pattern = '/\s*([^=\s]+)=\"([^\"]+)\";?/';
            preg_match_all($pattern, $disposition, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $fields[$match[1]] = $match[2];
            }

            $fields['contents'] = $value;
            $data[] = $fields;
        }

        return $data;
    }

    protected function assertRequestMultipartHasField($request, $field, $contents = null)
    {
        $has = false;
        $equal = false;
        $value = null;

        $parts = $this->parseMultipartBody($request->getBody());
        foreach ($parts as $fields) {
            if ($fields['name'] == $field) {
                $has = true;
                if ($contents === null) {
                    break;
                }

                $value = $fields['contents'];
                if ($contents === $value) {
                    $equal = true;
                    break;
                }
            }
        }

        if (!$has) {
            $this->assertTrue(false, "Field {$field} not found.");
        }

        if ($contents !== null && !$equal) {
            $this->assertEquals($contents, $value, "Field {$field} value not match");
        }
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

