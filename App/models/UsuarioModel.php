<?php

namespace models;

use lib\CustomException;

class UsuarioModel extends \Engine\Model
{
    public function __construct()
    {
        parent::__construct();

        $this->initDatabase('user');
    }

    public function buscarUsuarios()
    {
        $sqlQuery = "SELECT *
            FROM auth.users";

        return $this->container['user']->select($sqlQuery);
    }
}
