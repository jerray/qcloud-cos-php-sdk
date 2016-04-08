<?php

namespace jerray\QCloudCos;

use Carbon\Carbon;

class LocalStore implements Contracts\Store
{
    /**
     * @var array
     */
    static protected $data = [];

    /**
     * set
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expire
     * @return boolean
     */
    public function set($key, $value, $expire = 0)
    {
        self::$data[$key] = [
            'value' => $value,
            'expire' => $expire,
            'timestamp' => Carbon::now()->timestamp,
        ];

        return true;
    }

    /**
     * get
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            return null;
        }

        $data = self::$data[$key];
        return $data['value'];
    }

    /**
     * has
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        if (!array_key_exists($key, self::$data)) {
            return false;
        }

        $data = self::$data[$key];

        if ($data['expire'] && (Carbon::now()->timestamp - $data['timestamp']) > $data['expire']) {
            unset(self::$data[$key]);
            return false;
        }

        return true;
    }
}
