<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use Framework\Router;

$router = new Router();
$router->get('/home', [HomeController::class => 'index'], 'home');
//$router->add('/product/{sku:[\w-]+}', '/home');

return $router;