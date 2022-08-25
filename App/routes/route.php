<?php

use Controller\{AuthController,
                FormularioController,PdfController};

// Todas as rotas definidas dentro de group requerem autenticação
// Todas as rotas definidas fora de group não requerem autenticação
$app->group('', function ($app) {
    $app->get('/qualiTerm', FormularioController::class . ':getQualiTerm');
});

// Rotas sem autenticação
$app->post('/', AuthController::class . ':login')->setName("Login");
$app->get('/', AuthController::class . ':getLogin');
$app->get('/teste', AuthController::class . ':teste');
$app->get('/teste2', AuthController::class . ':teste2');
$app->get('/testeMem/{arr_size}', AuthController::class . ':testeMem');
$app->get('/testeCpu/{arr_size}', AuthController::class . ':testeCpu');



// A FAZER Middleware de Log deve ser adicionada antes da Middleware de Auth
