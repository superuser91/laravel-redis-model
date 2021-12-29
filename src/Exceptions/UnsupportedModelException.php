<?php

namespace Vgplay\LaravelRedisModel\Exceptions;

use Exception;

class UnsupportedModelException extends Exception
{
    public function __construct($message = 'Model chưa implement Cacheable interface.')
    {
        parent::__construct($message);
    }
}
