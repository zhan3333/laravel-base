<?php


namespace Zhan3333\Swoole;


use Swoole\Process;

class SwooleManager
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getStatus()
    {
        return $this->isRun() ? 'run' : 'stop';
    }

    public function isRun()
    {
        $pid = $this->getPid();
        if ($pid) {
            return Process::kill($pid, 0);
        }
        return false;
    }

    public function getPid(): int
    {
        $pid = null;
        // try get pid from pid_file
        if (file_exists($this->config['pid_file'])) {
            $pid = @file_get_contents($this->config['pid_file']);
        }
        // try get pid from pidof
        if (!$pid) {
            $pid = @shell_exec("pidof {$this->config['name']}");
            if ($pid) {
                $pid = str_replace("\n", '', $pid);
            }
        }
        return (integer)$pid;
    }

    public function stop()
    {
        Process::kill($this->getPid());
    }

    public function reload()
    {
        shell_exec("kill -USR1 {$this->getPid()}");
        Process::kill($this->getPid(), SIGUSR1);
    }

    public function checkConfig()
    {
        if (empty($this->config)) {
            return 'Config is empty';
        }
        if (empty($this->config['host'])) {
            return 'Config host is empty';
        }
        if (empty($this->config['port'])) {
            return 'Config host is empty';
        }
        if (($logFile = $this->config['log_file']) && !is_writable($logFile)) {
            return "Config $logFile not writable";
        }
        $pidFile = $this->config['pid_file'];
        $this->createPidFile($pidFile);
        if ($pidFile && !is_writable($pidFile)) {
            return "Config $pidFile not writable";
        }
    }

    public function createPidFile($pidPath)
    {
        $dir = $concurrentDirectory = substr($pidPath, 0, strrpos($pidPath, '/'));
        if (!is_dir($dir)) {
            if (!mkdir($dir) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Pid directory "%s" was not created', $concurrentDirectory));
            }
        }
        if (!is_file($pidPath)) {
            @touch($pidPath);
        }

    }
}
