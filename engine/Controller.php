<?php

namespace Engine;

// Inclusão de constantes
include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'constants.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use lib\Validator;
use lib\CustomException;

abstract class Controller
{
    protected $container;
    protected $view;
    protected $flash;
    protected $state;
    protected $config;
    protected $validator;

    public function __construct(ContainerInterface $container)
    {
        $this->dir = str_replace("engine", "", __DIR__);
        $this->container = $container;
        $this->view = $this->container->get('view');
        $this->config = $this->container->get('config');
        $this->validator = new Validator();
    }

    public function loadModel($model)
    {
        $class = '\\models\\' . $model . 'Model';
        return new $class();
    }

    public function loadService($service)
    {
        $class = '\\services\\' . $service . 'Service';
        return new $class($this->container);
    }

    public function loadTransformer($transformer)
    {
        $class = '\\transformers\\' . $transformer . 'Transformer';
        return new $class($this->container);
    }

    // Compara as chaves esperadas de uma requisição com as chaves devidamente inseridas na requisição
    public function checkExpectedKeys($expectedKeys, $data)
    {
        foreach ($expectedKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new CustomException('O parâmetro ' . $key . ' não foi informado na requisição.', 422);
            }
        }

        return true;
    }

    public function getApi($apiName)
    {
        $availableApis = parse_ini_file($this->dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'api.ini', true);

        if ($availableApis[$apiName]) {
            return $availableApis[$apiName];
        } else {
            return false;
        }
    }
}
