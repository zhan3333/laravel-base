<?php

use Illuminate\Foundation\Application;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Facade;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Zhan3333\Swoole\Exceptions\JobRuntimeException;
use Zhan3333\Swoole\Http\Boot;
use Zhan3333\Swoole\Http\TransformRequest;
use Zhan3333\Swoole\Job\Inject;

define('LARAVEL_START', microtime(true));

if (empty($argv[1])) {
    throw new Exception('base path must input');
}

define('APP_ROOT', $argv[1]);

if (!file_exists(APP_ROOT . '/vendor/autoload.php')) {
    throw new Exception(APP_ROOT . '/vendor/autoload.php not exists');
}

//require $basePath . '/vendor/autoload.php';

//$boot = new \Zhan3333\Swoole\Http\Boot($basePath);
//
//$boot->mainApp->make(\Zhan3333\Swoole\SwooleManager::class)->start();

class HttpServer
{
    // Manager

    public $config;

    /**
     * @var Server
     */
    public $server;

    public $test;

    /** @var Application */
    public $app;

    public function __construct($config)
    {
        $this->config = $config;
        $this->server = new \Swoole\Http\Server($this->config['host'], $this->config['port']);
        $this->server->set($this->config);
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('ManagerStart', [$this, 'onManagerStart']);
        $this->server->on('Connect', [$this, 'onConnect']);
        $this->server->on('Receive', [$this, 'onReceive']);
        $this->server->on('Close', [$this, 'onClose']);
        $this->server->on('Task', [$this, 'onTask']);
        $this->server->on('Finish', [$this, 'onFinish']);
        $this->server->on('Request', [$this, 'onRequest']);
        $this->server->on('Finish', [$this, 'onFinish']);
    }

    public function start()
    {
        $this->server->start();
    }

    public function onWorkerStart(Server $server, int $worker_id): void
    {
        require APP_ROOT . '/vendor/autoload.php';

        $boot = new Boot();
        $this->app = $boot->mainApp;
        try {
            $this->log(($server->taskworker ? 'Task' : 'Worker') . " start, id: $worker_id");
            swoole_set_process_name($this->config['name'] . ($server->taskworker ? '-task' : '-worker'));
        } catch (Exception $exception) {
            $this->app->make(\Illuminate\Foundation\Exceptions\Handler::class)->report($exception);
        } catch (Error $exception) {
            $this->app->make(\Illuminate\Foundation\Exceptions\Handler::class)->report($e = new FatalThrowableError($exception));
        }
    }

    public function onManagerStart(Server $server): void
    {
        $this->log("manager start, pid: $server->manager_pid");
        swoole_set_process_name($this->config['name'] . '-manager');
    }

    public function onStart(Server $server): void
    {
        $this->log('Start');
        swoole_set_process_name($this->config['name']);
    }

    // TCP service

    public function onConnect(Server $server, int $fd, int $reactorId)
    {
        $this->log("Client $fd: Connect.");
    }

    public function onReceive(Server $server, int $fd, int $reactor_id, string $data)
    {
        $this->log("Receive Client $fd", $data);
        //投递异步任务
        $task_id = $server->task($data);
        $this->log("Send to Task: $task_id", $data);
//        $sendRet = $server->send($fd, "$server->worker_id-$task_id");
//        $this->log("Send to Client: $fd result: $sendRet", $server->worker_id - $task_id);
    }

    public function onClose($serv, $fd)
    {
        $this->log("Client $fd: Close.");
    }

    // Task

    public function onTask(Server $serv, int $task_id, int $src_worker_id, $data)
    {
        $this->log("Task: $src_worker_id-$task_id start", $data);
        $payload = json_decode($data, true);
        $jobId = $payload['id'];
        [$class, $method] = $payload['job'];
        $instant = new $class(...Inject::fullConstructParams($class, $payload['data']));
        // 写入 jobId
        $instant->jobId = $jobId;
        // 执行 handle method
        try {
            $result = $instant->{$method}(...Inject::getInjectParams($class, $method));
            if (method_exists($instant, 'finish')) {
                $instant->finish($jobId, $result);
            }
        } catch (\Exception $exception) {
            $this->log("Task $serv->worker_id-$task_id exception", $exception->getMessage());
            if (method_exists($instant, 'failed')) {
                $instant->failed($jobId, JobRuntimeException::copy($exception));
            }
        }

    }

    public function onFinish(Server $serv, $task_id, $data)
    {
        $this->log("Task $serv->worker_id-$task_id finish", $data);
    }

    public function onRequest(Request $request, Response $response)
    {
        try {

            $app = $this->app;
//            $app->flush();

            $transformRequest = TransformRequest::handle($request);

            $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

            $app->instance('request', $transformRequest);

            Facade::clearResolvedInstance('request');

            /** @var \Illuminate\Http\Response $transformResponse */
            $transformResponse = (new Pipeline($app))
                ->send($transformRequest)
                ->through($app->shouldSkipMiddleware() ? [] : $kernel->middleware)
                ->then($kernel->dispatchToRouter());

        } catch (Exception $e) {
            $app[\App\Exceptions\Handler::class]->report($e);
            $transformResponse = $app[\App\Exceptions\Handler::class]->render($request, $e);
        } catch (Throwable $e) {
            $app[\App\Exceptions\Handler::class]->report($e = new FatalThrowableError($e));
            $transformResponse = $app[\App\Exceptions\Handler::class]->render($request, $e);
        }
        foreach ($transformResponse->headers as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $response->header($key, $value);
        }
        $response->status($transformResponse->getStatusCode());
        $response->end($transformResponse->content());
    }

    private function log($message, $data = [])
    {
        if (!is_array($data)) {
            $data = [$data];
        }
        if ($this->config['log_enable'] && isset($this->app[\Zhan3333\Swoole\SwooleLogger::class])) {
            $this->app[\Zhan3333\Swoole\SwooleLogger::class]->info("{$this->config['name']}: $message", $data);
        }
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '')
    {
        return APP_ROOT . '/storage/' . $path;
    }
}

$config = require APP_ROOT . '/config/swoole.php';
$driver = $config['use_server'];
$serverConfig = $config['services'][$driver];

$server = new HttpServer($serverConfig);

$server->start();
