<?php

namespace services;

use lib\CustomException;

class UsuarioService extends \Engine\Service
{
    public $validator;

    public function cadastrar($body)
    {
            $rules = array (
                'email' => REQUIRED . "|email",
                'password' => REQUIRED . '|between:6,16',
                'name' => REQUIRED . '|between:4,40'
            );

            $this->validator->validate($body, $rules);

            $usuarioModel = $this->loadModel('Usuario');

            $usuario = $usuarioModel->buscarUsuario($body['email']);

            if (isset($usuario[0]) && !empty($usuario[0])) {
                throw new CustomException('Já existe um cadastro com esse email.', 500);
            }

            $body['password'] = hash('sha256', $body['password']);
            $usuario = $usuarioModel->inserirUsuario($body);
            if (isset($usuario[0]['password'])) {
                $usuario[0]['password'] = '***';
                $usuario[0]['2'] = '***';
            }
            return $usuario;
    }

    public function recuperarSenha($body)
    {
            $rules = array (
                'password' => REQUIRED . '|between:6,16',
                'codigo' => REQUIRED . '|between:19,21'
            );

            $this->validator->validate($body, $rules);

            $usuarioModel = $this->loadModel('Usuario');

            $usuario = $usuarioModel->buscarCodigoRecuperacao($body['codigo']);
            //var_dump($usuario);
            if (!isset($usuario[0]) || empty($usuario[0])) {
                throw new CustomException('Código de recuperação invalido.', 500);
            }

            $body['password'] = hash('sha256', $body['password']);
            $usuarioModel->alterarSenha($body['password'], $usuario[0]['email']);
            $usuarioModel->alterarCodigoRecuperacao($usuario[0]['email']);

            return 'Senha alterada com sucesso';
    }
}
