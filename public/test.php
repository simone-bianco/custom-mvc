<?php

require __DIR__ . '/index.php';

$router = new \Framework\Router();

$router->get('test', '/test/{id}', [\App\Controllers\HomeController::class => 'index']);

var_dump($router->match('/test/5', 'get'));