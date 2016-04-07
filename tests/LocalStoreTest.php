<?php

namespace jerray\QCloudCos\Tests;

use Faker\Factory as Faker;
use jerray\QCloudCos\LocalStore;
use TestCase;

class LocalStoreTest extends TestCase
{
    public function setUp()
    {
        $this->faker = Faker::create();
        $this->store = new LocalStore();
    }

    public function testSet()
    {
        $key = $this->faker->userName;
        $result = $this->store->set($key, $this->faker->randomNumber);
        $this->assertTrue($result);
	}

    public function testHas()
    {
        $key = $this->faker->userName;
        $value = $this->faker->randomNumber;
        $result = $this->store->set($key, $value);

        $this->assertTrue($this->store->has($key));
    }

    public function testGet()
    {
        $key = $this->faker->userName;
        $value = $this->faker->randomNumber;

        $result = $this->store->set($key, $value);
        $this->assertEquals($value, $this->store->get($key));
    }
}

