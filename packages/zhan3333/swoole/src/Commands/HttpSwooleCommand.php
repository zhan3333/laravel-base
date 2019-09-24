<?php
/**
 * User: zhan
 * Date: 2019/9/24
 * Email: <grianchan@gmail.com>
 */

namespace Zhan3333\Swoole\Commands;


use Illuminate\Console\Command;

class HttpSwooleCommand extends Command
{
    protected $signature = 'http-swoole:start';

    protected $description = '启动Http服务';

    public function handle()
    {
        $serverPath = dirname(__DIR__) . '/Bin/server.php';
        $basePath = app()->basePath();
        $this->info("run: php $serverPath '$basePath'");
        shell_exec("php $serverPath '$basePath'");
    }
}
