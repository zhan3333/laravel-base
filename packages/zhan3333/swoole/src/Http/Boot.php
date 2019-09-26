<?php
/**
 * User: zhan
 * Date: 2019/9/26
 * Email: <grianchan@gmail.com>
 */

namespace Zhan3333\Swoole\Http;


use App\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

class Boot
{
    /** @var Application */
    public static $mainApp;

    public static function boot($basePath = '')
    {
        if (empty($basePath) && self::$mainApp) {
            $basePath = self::$mainApp->basePath();
        }

        $app = new Application(
            $_ENV['APP_BASE_PATH'] ?? $basePath
        );

        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            Kernel::class
        );

        $app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );

        $app->singleton(
            ExceptionHandler::class,
            Handler::class
        );

        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
        $app->instance('request', Request::capture());
        $kernel->bootstrap();

        if (!self::$mainApp) {
            self::$mainApp = $app;
        }

        return $app;
    }
}
