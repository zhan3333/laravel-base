<?php

use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\Debug\Exception\FatalThrowableError;

define('LARAVEL_START', microtime(true));

if (empty($argv[1])) {
    throw new Exception('base path must input');
}

$basePath = $argv[1];

if (!file_exists($basePath . '/vendor/autoload.php')) {
    throw new Exception("$basePath/vendor/autoload.php not exists");
}

require $basePath . '/vendor/autoload.php';

$app = \Zhan3333\Swoole\Http\Boot::boot($basePath);

$app->make(\Zhan3333\Swoole\SwooleManager::class)->start();
