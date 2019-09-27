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
    public $mainApp;

    public function __construct()
    {
        $this->mainApp = $this->bootstrap(APP_ROOT);
    }

    public function copy()
    {
        return $this->bootstrap($this->mainApp->basePath());
    }

    private function bootstrap($basePath)
    {
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
        return $app;
    }
}
