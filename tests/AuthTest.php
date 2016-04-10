<?php

namespace jerray\QCloudCos\Tests;

use Faker\Factory as Faker;
use jerray\QCloudCos\Auth;
use jerray\QCloudCos\LocalStore;
use TestCase;

class AuthTest extends TestCase
{
    public function setUp()
    {
        $faker = Faker::create();

        $this->appId = $faker->randomNumber;
        $this->bucket = $faker->userName;
        $this->secretId = $faker->md5;
        $this->secretKey = $faker->md5;
        $this->fileid = $faker->md5 . '.' . $faker->fileExtension;

        $this->storeKey = "jerray:qcloud:cos:sign:{$this->bucket}";

        $this->store = new LocalStore();
        $this->auth = new Auth($this->appId, $this->secretId, $this->secretKey, $this->store);
    }

    public function testGenerateSign()
    {
        $sign = $this->auth->generateSign($this->bucket);
        $this->assertEquals($sign, $this->store->get($this->storeKey), "Sign should be stored in the store");

        $sign1 = $this->auth->generateSign($this->bucket);
        $this->assertEquals($sign, $sign1, "Sign should be got from store");

        $sign2 = $this->auth->generateSign($this->bucket, 60, true);
        $this->assertNotEquals($sign, $sign2, "Sign should be regenerated");
    }

    public function testGenerateOneTimeSign()
    {
        $sign = $this->auth->generateOneTimeSign($this->fileid, $this->bucket, true);
        $decoded = base64_decode($sign);
        $this->assertContains("f=/{$this->appId}/{$this->bucket}/{$this->fileid}", $decoded, "Field f should be file's full path");
        $this->assertContains("&e=0&t=", $decoded, "Field e should be 0");

        $sign = $this->auth->generateOneTimeSign($this->fileid, $this->bucket, false);
        $decoded = base64_decode($sign);
        $this->assertContains("f=/{$this->fileid}", $decoded, "Field f should be file's relative path");
    }
}

