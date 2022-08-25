<?php

namespace Engine;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

abstract class RestClient
{
    protected $dir;

    public function getApi($apiName)
    {
        $this->dir = str_replace("engine", "", __DIR__);

        $availableApis = parse_ini_file($this->dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'api.ini', true);

        if ($availableApis[$apiName]) {
            return $availableApis[$apiName];
        } else {
            return false;
        }
    }
}
