<?php

use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\Debug\Exception\FatalThrowableError;

define('LARAVEL_START', microtime(true));

var_dump($argv);

if (empty($argv[1])) {
    throw new Exception('base path must input');
}

$basePath = $argv[1];

if (!file_exists($basePath . '/vendor/autoload.php')) {
    throw new Exception("$basePath/vendor/autoload.php not exists");
}

require $basePath . '/vendor/autoload.php';

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? $basePath
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    \Zhan3333\Swoole\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

app()->instance('request', Illuminate\Http\Request::capture());

$kernel->bootstrap();

$app->make(\Zhan3333\Swoole\SwooleManager::class)->start();
