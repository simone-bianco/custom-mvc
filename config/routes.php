<?php

use App\Controllers\HomeController;
use Framework\Router;

$router = new Router();

$router->add('home', '/home', [HomeController::class => 'index']);
//$router->add('/product/{sku:[\w-]+}', '/home');

return $router;