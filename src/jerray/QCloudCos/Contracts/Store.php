<?php

namespace jerray\QCloudCos\Contracts;

interface Store
{
    public function set($key, $value, $expire);

    public function get($key);

    public function has($key);
}
