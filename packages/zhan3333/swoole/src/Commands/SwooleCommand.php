<?php


namespace Zhan3333\Swoole\Commands;

use Illuminate\Console\Command;
use Swoole\Process;
use Zhan3333\Swoole\SwooleManager;

class SwooleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole {action=status : start|stop|reload|status|pid}}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'swoole start|stop|reload|status|pid';

    /**
     * Swoole config
     * @var array
     */
    protected $config;

    /**
     * Swoole process name
     * @var mixed
     */
    protected $swooleName = '';

    /** @var SwooleManager */
    protected $manager;

    public function __construct()
    {
        parent::__construct();
        $this->manager = app(SwooleManager::class);
        $this->config = $this->manager->getConfig();
        $this->swooleName = $this->config['name'];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $arg = $this->argument('action');

        switch ($arg) {
            case 'start':
                $this->start();
                break;
            case 'reload':
                $this->reload();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'status':
                $this->status();
                break;
            case 'pid':
                $this->pid();
                break;
            default:
                break;
        }
    }

    protected function start()
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $this->info("Start $this->swooleName $host:$port ...");
        if (!$this->manager->isRun()) {
            if ($error = $this->manager->checkConfig()) {
                $this->error("$this->swooleName can't start: $error");
            } else {
                $this->manager->start();
            }
            // 被阻塞，后面在进程结束钱是不会执行的
        } else {
            $this->error("$this->swooleName already run.");
        }
    }

    protected function reload()
    {
        $name = $this->swooleName;
        $this->info("Reload $name ...");
        if (!$this->manager->isRun()) {
            $this->warn("$name not run.");
            if ($error = $this->manager->checkConfig()) {
                $this->error("$this->swooleName can't start: $error");
            } else {
                $this->manager->start();
            }
        } else {
            $this->manager->reload();
            $this->info("Reload $name success");
        }
    }

    protected function stop()
    {
        $this->info('Stop ' . $this->swooleName . ' start.');
        if (!$this->manager->isRun()) {
            $this->error("$this->swooleName not run.");
        } else {
            $this->manager->stop();
            $this->info('Stop ' . $this->swooleName . ' success.');
        }
    }

    protected function status()
    {

        $this->info($this->swooleName . ' status: ' . $this->manager->getStatus());
    }

    protected function pid()
    {
        $this->info("$this->swooleName pid is {$this->manager->getPid()}");
    }
}
