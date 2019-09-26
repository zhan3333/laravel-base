<?php
/**
 * User: zhan
 * Date: 2019/9/26
 * Email: <grianchan@gmail.com>
 */

namespace Zhan3333\Swoole\Http;


use Illuminate\Foundation\Application;

class Boot
{
    /** @var Application */
    public static $mainApp;

    public static function boot($basePath = '')
    {
        if (empty($basePath) && self::$mainApp) {
            $basePath = self::$mainApp->basePath();
        } else {
            throw new \Exception('Base path must set');
        }

        $app = new \Illuminate\Foundation\Application(
            $_ENV['APP_BASE_PATH'] ?? $basePath
        );

        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            \Zhan3333\Swoole\Http\Kernel::class
        );

        $app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );

        $app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
        $app->instance('request', \Illuminate\Http\Request::capture());
        $kernel->bootstrap();

        if (!self::$mainApp) {
            self::$mainApp = $app;
        }

        return $app;
    }
}
