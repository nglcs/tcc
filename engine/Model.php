<?php

namespace Engine;

use lib\PDOHelper;
use lib\Validator;
use lib\CustomException;

abstract class Model
{
    protected $databases;
    protected $config;
    protected $envState;
    protected $container;
    protected $validator;
    private $errorMessage;
    protected $dir;
    protected $configDir;

    public function __construct()
    {
        $this->dir = str_replace("engine", "", __DIR__);
        $this->configDir = $this->dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $this->databases = parse_ini_file($this->configDir . 'database.ini', true);
        $this->config = parse_ini_file($this->configDir . 'config.ini', true);
        $this->container = array();
        $this->validator = new Validator();
        $this->setEnvState();
    }

    public function loadTransformer($transformer)
    {
        $class = '\\transformers\\' . $transformer . 'Transformer';
        return new $class($this->container);
    }

    public function setEnvState()
    {
        $this->states = parse_ini_file($this->dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'state.ini', true);
        $requestUri = 'REQUEST_URI';
        $developStr = 'develop';
        $server = $_SERVER;
        if (strpos($server[$requestUri], $this->states['homolog']) !== false) {
            $urlEnvParameter = 'homolog';
        } elseif (strpos($server[$requestUri], $this->states[$developStr]) !== false) {
            $urlEnvParameter = $developStr;
        } elseif (strpos($server[$requestUri], $this->states['training']) !== false) {
            $urlEnvParameter = $developStr;
        } else {
            $urlEnvParameter = 'default';
        }
        $this->envState = array_search($urlEnvParameter, $this->states);
        if (!$this->envState) {
            // Expected to be default
            $this->envState = $urlEnvParameter;
        }
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

    /**
     * Executa uma requisição GET
     *
     * @deprecated
     * @param string url
     * @param array parameters (with keys)
     * @param array headers (without keys)
     * @return array [data, http_code, header_size, result]
     */
    public function curlGET($url, $parameters, $headers = array())
    {
        if (
            !$url
            || !is_string($url)
            || !preg_match('/((http|https)\:\/\/)?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.([a-zA-Z0-9\&\.\/\?\:@\-_=#])*/', $url)
        ) {
            $this->errorMessage = "Url inválida";
            $this->handleError(1, $this->errorMessage);
        }

        // Checa se há a necessidade de adição de queries
        if (isset($parameters) && !empty($parameters)) {
            // Add get parameters to the url
            $parametersStr = http_build_query($parameters);
            if ($parametersStr) {
                $url .= "?" . $parametersStr;
            }
        }

        $curl  = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $result = curl_exec($curl);

        // Busca dados da requisição curl
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body = substr($result, $headerSize);

        curl_close($curl);

        return array("data" => $body, "http_code" => $httpcode, "header_size" => $headerSize, 'result' => $result);
    }

    /**
     * Executa uma requisição POST
     *
     * @deprecated
     * @param string url
     * @param array data
     * @param array headers (without keys)
     * @return array [data, response code]
     */
    public function curlPOST($url, $data, $headers = array())
    {
        if (
            !$url
            || !is_string($url)
            || !preg_match('/((http|https)\:\/\/)?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.([a-zA-Z0-9\&\.\/\?\:@\-_=#])*/', $url)
        ) {
            $this->errorMessage = "Url inválida";
            $this->handleError(1, $this->errorMessage);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array("data" => $result, "http_code" => $httpcode);
    }

    /**
     * Retorna somente os elementos permitidos em uma lista
     *
     * @param array $myArray - lista a ser filtrada
     * @param array $allowed - chaves permitidas
     * @return array - lista filtrada
     */
    public function filterAllowedArrayKeys($myArray, $allowed)
    {
        $filtered = array_filter(
            $myArray,
            function ($key) use ($allowed) {
                return in_array($key, $allowed);
            },
            ARRAY_FILTER_USE_KEY
        );
        return $filtered;
    }

    // Cria array de bindings através de um array associativo ($chave => $valor)
    public function createBindingsArray($parameters)
    {
        $bindingsArray = array();
        foreach ($parameters as $key => $value) {
            $bindingsArray[':' . $key] = $this->setValor($parameters, $key);
        }

        return $bindingsArray;
    }

    public function setValor($dados, $key)
    {
        if (isset($dados[$key]) && $dados[$key] === false) {
            $dados[$key] = "false";
            return $dados[$key];
        }
        return isset($dados[$key]) ? $dados[$key] : null;
    }

    public function setValorCheckbox($dados, $key)
    {
        return isset($dados[$key]) && $dados[$key] != '' ? $dados[$key] : 'false';
    }

    public function returnDate($date)
    {
        $data = array('date' => $date);
        $rules = array('date' => 'date_format:Y-m-d');
        if (!$this->validator->validate($data, $rules)['valid']) {
            $this->handleError(1, $this->errorMessage);
        } else {
            $dt = new \DateTime();
            $date = $dt->createFromFormat('Y-m-d', $date);
            return $date->format('Y-m-d');
        }
    }

    public function setDate($dados, $key)
    {
        $this->errorMessage = 'Formato de data inválido';
        if (!isset($dados[$key]) || !is_string($dados[$key])) {
            $this->handleError(1, $this->errorMessage);
        }
        return $this->returnDate($dados[$key]);
    }

    public function setTimeStamp($dados, $key)
    {
        $this->errorMessage = 'Formato de data inválido';
        if (!isset($dados[$key]) || !is_int($dados[$key])) {
            $this->handleError(1, $this->errorMessage);
        }
        return $this->returnDate($dados[$key]);
    }

    /**
     * Lida com os erros, printando somente caso não esteja em ambiente de produção
     *
     * @param integer $errorClass - tipo do erro
     * Valores aceitos:
     *      1 - UnexpectedValueException
     * @param string $message - message to be printed
     * @return
     */
    public function handleError($errorClass, $message)
    {
        if ($this->envState == 'default') {
            if ($errorClass == 1) {
                throw new \UnexpectedValueException($message);
            } else {
                throw new \Exception($message);
            }
        }
    }

    public function initDatabase($db)
    {
        if (!isset($this->container[$db])) {
            $this->container[$db] = (object) $this->openConnect($db);
        }
    }

    public function begin($db)
    {
        $this->container[$db]->beginTransaction();
    }
    public function commit($db)
    {
        $this->container[$db]->commit();
    }
    public function rollBack($db)
    {
        $this->container[$db]->rollBack();
    }


    public function openConnect($database)
    {
        $dbData = $this->databases[$this->envState . '_' . $database];

        $dsn = $dbData['type'] . ':host=' . $dbData['host'] . ';port=' . $dbData['port'];
        $dsn .= ';dbname=' . $dbData['dbname'];
        $user = $dbData['user'];
        $pass = $dbData['password'];

        return new PDOHelper($dsn, $user, $pass, $this->envState, $dbData['type'], $this->config, []);
    }

    /**
     * Retorna o IP do Cliente.
     *
     * @return void
     */
    public function getIP()
    {
        $server = $_SERVER;
        if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $server['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($server['HTTP_CLIENT_IP'])) {
            $ip = $server['HTTP_CLIENT_IP'];
        } else {
            $ip = $server['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Formata um array para se adequar a clausula IN
     *
     * @param mixed $arr
     *
     * @return [type]
     */
    public function createInClause($arr)
    {
        return '\'' . implode('\', \'', $arr) . '\'';
    }
}
