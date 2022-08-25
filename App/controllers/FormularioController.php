<?php

namespace Controller;

class FormularioController extends \Engine\Controller
{
    public function getCadastro($request, $response, $args)
    {
        return $this->view->render($response, 'cadastro.html');
    }
}
