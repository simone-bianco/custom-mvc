<?php

require __DIR__ . '/index.php';

$dotenv = new Framework\Dotenv;

$dotenv->load("../.env");

var_dump($_ENV);