<?php

namespace RestClients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class ibge extends \Engine\RestClient
{
    protected $apiConfig;

    public $clientIbge;

    public function __construct()
    {
        $this->apiConfig = $this->getApi('ibge');
        if ($this->apiConfig) {
            $this->clientIbge = new Client($this->apiConfig);
        } else {
            return false;
        }
    }

    public function getMunicipios(string $uf)
    {
        $response = $this->clientIbge->get('localidades/estados/' . $uf . '/municipios');
        $body = $response->getBody();
        return json_decode($body);
    }
}