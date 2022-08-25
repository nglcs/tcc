<?php

namespace lib;

class CustomException extends \Exception
{
    protected $errorType;
    protected $errorCode;
    protected $errorMessage;

    // Inicializa atributos e métodos de herança
    public function __construct($errorMessage = 'Mensagem de erro não informada', $errorCode = 0, Exception $previous = null)
    {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->errorType = $this->setErrorType();

        parent::__construct($errorMessage, $errorCode, $previous);
    }

    // Seta o tipo de erro para auxiliar na mensagem de retorno
    public function setErrorType()
    {
        $type = "Tipo do erro não informado.";

        if ($this->errorCode === 404) {
            $type = "Resultado não encontrado.";
        } elseif ($this->errorCode === 409) {
            $type = "O registro já existe e está havendo conflito.";
        } elseif ($this->errorCode === 422) {
            $type = "Parâmetros inválidos ou inexistentes.";
        } elseif ($this->errorCode === 500) {
            $type = "Erro interno do servidor.";
        } elseif ($this->errorCode === 200) {
            $type = "Método executado com sucesso.";
        } elseif ($this->errorCode === 503) {
            $type = "Serviço indisponível";
        }

        return $type;
    }

    // Retorna a mensagem completa de exceção
    public function getCompleteExceptionMessage()
    {
        return array (
            "erro" => $this->errorType,
            "codigo" => $this->errorCode,
            "mensagem" => $this->errorMessage
        );
    }
}
