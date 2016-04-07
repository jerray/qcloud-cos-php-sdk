<?php

namespace jerray\QCloudCos\Exceptions;

use Exception;

class ClientException extends Exception
{
    protected $body;

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }
}
