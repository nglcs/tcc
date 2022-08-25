<?php

namespace Engine;

use Slim\Http\UploadedFile;
use lib\CustomException;

abstract class Service
{
    protected $state;
    protected $config;
    protected $validator;
    protected $container;

    public function __construct()
    {
        $this->dir = str_replace("engine", "", __DIR__);
        $this->configDir = $this->dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $this->config = parse_ini_file($this->configDir . 'config.ini', true);
        $this->validator = new \lib\Validator();
    }

    public function loadModel($model)
    {
        $class = '\\models\\' . $model . 'Model';
        return new $class();
    }

    public function loadTransformer($transformer)
    {
        $class = '\\transformers\\' . $transformer . 'Transformer';
        return new $class($this->container);
    }

    public function loadService($service)
    {
        $class = '\\services\\' . $service . 'Service';
        return new $class($this->container);
    }

    /**
     * Move o arquivo para uma pasta especifica e retorna um nome
     *
     * @param mixed $directory
     * @param UploadedFile $uploadedFile
     *
     * @return [type]
     */
    public function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    /**
     * Salva arquivos na pasta Upload
     *
     * @param array $file - Array com a chave 'files' contendo os arquivos a serem salvos
     *
     * @return array - Array com o hash como chave e o nome do arquivo como valor
     */
    public function salvarArquivos(array $file)
    {
        if (!isset($file['files'])) {
            throw new CustomException("Arquivo não encontrado.", 422);
        }
        $directory = $this->dir . "uploads";
        $uploaded = array();

        foreach ($file['files'] as $uploadedFile) {
            if ($uploadedFile->getSize() > $this->config['upload_file']['max_upload_size']) {
                throw new CustomException("O arquivo não pode ser maior que 10MB." . '[' . $uploadedFile->getClientFilename() . ']', 422);
            }

            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                $filename = $this->moveUploadedFile($directory, $uploadedFile);
                $documentoHash = hash_file('md5', $directory . DIRECTORY_SEPARATOR . $filename);
                $uploaded[$documentoHash] =  $filename;
            } else {
                throw new CustomException("Falha ao carregar arquivo." . $uploadedFile->getClientFilename(), 422);
            }
        }
        return $uploaded;
    }
}
