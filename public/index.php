<?php

declare(strict_types=1);

define("ROOT_PATH", dirname(__DIR__));

spl_autoload_register(function (string $className) {
    require ROOT_PATH . "/src/" . str_replace("\\", "/", $className) . ".php";
});

new Framework\Dotenv()->load(ROOT_PATH . '/.env');

set_error_handler('Framework\ErrorHandler::handleError');
set_exception_handler('Framework\ErrorHandler::handleException');