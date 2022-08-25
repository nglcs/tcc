<?php

namespace Controller;

use GuzzleHttp\Client;
use lib\PDOHelper;
use Firebase\JWT\JWT;
use lib\CustomException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class AuthController extends \Engine\Controller
{

    public function teste($request, $response, $args)
    {
        $unidades = array ('cras', 'creas', 'centropop', 'correios', 'upa', 'ubs', 'conselho_tutelar', 'dpu', 'dpf', 'escolas', 'agencia_inss');
        $mopsClient = new \RestClients\mops();
        //Falhas ubs, 
        $res = $mopsClient->getDistritos('upa');
        $res2 = strstr($res, '{');
        //return $response->write($res2);
        return $response->withJSON(json_decode($res2));
    }

    public function teste2($request, $response, $args)
    {
        $ufs = array('11', '12', '13', '14', '15', '16', '17', '21', '22','23','24','25','26','27','28','29','31','32','33','35','41','42','43','50','51','52','53');
        $municipios = array();
        $ibgeClient = new \RestClients\ibge();
        $res = $ibgeClient->getMunicipios('25');
        $i = 0;
        foreach ($res as $municipio) {
            $municipios[$i]['id'] = $municipio->id;
            $municipios[$i]['nome'] = $municipio->nome;
            $i++;
        }
        return $response->withJSON($municipios);
    }

    public function testeMem($request, $response, $args){

        
        $memInicial = memory_get_usage();
        $metricas = array('memoria_inicial' => $memInicial, 'memoria_inicial_kb' => round($memInicial/1000, 2));
        $array = array_fill(0, $args['arr_size'], random_int(100, 9999));
        $array2 = array_fill(0, $args['arr_size'], random_int(100, 9999));
        $metricas['memoria_final'] = memory_get_usage();
        $metricas['memoria_final_kb'] = round($metricas['memoria_final']/1000, 2);
        sleep(2);
        return $response->withJSON($metricas);

        // Remove the array from memory
        //unset($array);
    }

    public function testeCpu($request, $response, $args){

        
        //$cpuInicial = sys_loadavg()[0]; // Only linux


        $i = 0;
        while ($i < $args['arr_size']) {
            $i++;  
        }

        return $response->withJSON(array('i' => $i));
    }
}
