<?php
/**
 * User: zhan
 * Date: 2019/9/24
 * Email: <grianchan@gmail.com>
 */

namespace Zhan3333\Swoole\Commands;


use Illuminate\Console\Command;
use Zhan3333\Swoole\SwooleManager;

class HttpSwooleCommand extends Command
{
    private $supportCommands = [
        'start', 'start', 'stop', 'reload', 'restart',
    ];

    protected $signature = '';

    protected $description = '启动Http服务';

    /** @var SwooleManager */
    private $manager;

    /** @var array */
    private $config;

    public function __construct()
    {
        $this->signature = 'http-swoole {action=status : ' . $this->supportCommandsStr() . '}';
        $this->manager = app(SwooleManager::class);
        $this->config = $this->manager->getConfig();
        parent::__construct();
    }

    private function supportCommandsStr()
    {
        return implode('|', $this->supportCommands);
    }

    public function handle()
    {
        $action = $this->argument('action');
        if (method_exists($this, $action)) {
            $this->{$action}();
        } else {
            $this->error('Action only support: ' . $this->supportCommandsStr());
        }
    }

    public function status()
    {
        $this->info(app(SwooleManager::class)->getStatus());
    }

    public function start()
    {
        if ($this->manager->isRun()) {
            $this->warn("{$this->config['host']}:{$this->config['port']} already use");
        } else {
            $serverPath = dirname(__DIR__) . '/Bin/server.php';
            $basePath = app()->basePath();
            $this->info("run: php $serverPath '$basePath'");
            shell_exec("php $serverPath '$basePath'");
        }
    }

    public function stop()
    {
        $this->manager->stop();
        while (1) {
            if (!$this->manager->isRun()) {
                $this->info('Stop success');
                break;
            }
        }
    }

    public function reload()
    {
        if ($this->manager->isRun()) {
            $this->info('Server is running, reload ...');
            $this->manager->reload();
            $this->info('Reload success');
        } else {
            $this->warn('Server not running');
        }
    }

    public function restart()
    {
        if ($this->manager->isRun()) {
            $this->info('Server is running, restart ...');
            $this->manager->stop();
            $this->info('Stop success');
            $this->info('Start ...');
            while (1) {
                if (!$this->manager->isRun()) {
                    $this->start();
                    $this->info('Start success');
                    break;
                }
            }
        } else {
            $this->start();
            $this->info('Start success');
        }
    }
}
