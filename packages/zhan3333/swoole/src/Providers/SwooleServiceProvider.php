<?php

namespace Zhan3333\Swoole\Providers;

use Illuminate\Support\ServiceProvider;
use Zhan3333\Swoole\Commands\HttpSwooleCommand;
use Zhan3333\Swoole\SwooleLogger;
use Zhan3333\Swoole\SwooleManager;

class SwooleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                HttpSwooleCommand::class,
            ]);
        }
        $this->publishes([
            __DIR__ . '/../../config/swoole.php' => config_path('swoole.php'),
        ]);

    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/swoole.php', 'swoole'
        );
        $this->app->singleton(SwooleManager::class, function () {
            $driver = config('swoole.use_server');
            if (!config("swoole.services.{$driver}")) {
                throw new \RuntimeException("config: swoole.services.{$driver} not set");
            }
            $config = config("swoole.services.{$driver}");
            return new SwooleManager($config);
        });
        $this->app->singleton(SwooleLogger::class, function ($app) {
            $driver = config('swoole.use_server');
            if (config("swoole.services.{$driver}.log_enable", false)) {
                return new SwooleLogger($app['log']->channel(config("swoole.services.{$driver}.log_channel")));
            }
            return new SwooleLogger();
        });
    }
}
