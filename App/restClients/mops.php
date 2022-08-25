<?php

namespace RestClients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class mops extends \Engine\RestClient
{
    protected $apiConfig;

    public $clientMops;

    public function __construct()
    {
        $this->apiConfig = $this->getApi('mops');
        //$this->apiConfig = 'https://aplicacoes.mds.gov.br/sagi/app-sagi/geosagi/';
        if ($this->apiConfig) {
            $this->clientMops = new Client($this->apiConfig);
        } else {
            return false;
        }
    }

    public function getDistritos(string $unidade)
    {
        $response = $this->clientMops->get('servicos/getEquipamentosDispByTipoCodigo.php?georref&t='. $unidade .'&uf=53&a=0');
        $body = $response->getBody();
        //return json_decode($body);
        return $body;
    }
}