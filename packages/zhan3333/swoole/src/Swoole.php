<?php


namespace Zhan3333\Swoole;


use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Facade;
use Psr\Log\LoggerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;
use Zhan3333\Swoole\Exceptions\JobRuntimeException;
use Zhan3333\Swoole\Http\Kernel;
use Zhan3333\Swoole\Http\TransformRequest;
use Zhan3333\Swoole\Http\TransformResponse;
use Zhan3333\Swoole\Job\Inject;

class Swoole
{
    // Manager

    private $config;

    /**
     * @var Server
     */
    public $server;

    public function __construct($config)
    {
        $this->config = $config;
        $server = new \Swoole\Http\Server($this->config['host'], $this->config['port']);
        $server->set($this->config);
        $server->on('Start', [$this, 'onStart']);
        $server->on('WorkerStart', [$this, 'onWorkerStart']);
        $server->on('ManagerStart', [$this, 'onManagerStart']);
        $server->on('Connect', [$this, 'onConnect']);
        $server->on('Receive', [$this, 'onReceive']);
        $server->on('Close', [$this, 'onClose']);
        $server->on('Task', [$this, 'onTask']);
        $server->on('Finish', [$this, 'onFinish']);
        $server->on('Request', [$this, 'onRequest']);
        $server->on('Finish', [$this, 'onFinish']);
        $this->server = $server;
    }

    public function start()
    {
        $this->server->start();
    }


    public function onWorkerStart(Server $server, int $worker_id): void
    {
        $this->log(($server->taskworker ? 'Task' : 'Worker') . " start, id: $worker_id");
        swoole_set_process_name($this->config['name'] . ($server->taskworker ? '-task' : '-worker'));
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
        $transformRequest = TransformRequest::handle($request);
        $transformResponse = $this->sandbox($transformRequest);
        TransformResponse::handle($transformResponse, $response);
        $response->end('test');
//        $response->end($transformResponse->content());
    }

    private function sandbox(\Illuminate\Http\Request $request): \Illuminate\Http\Response
    {
        /** @var Application $app */
        $app = new \Illuminate\Foundation\Application(
            $_ENV['APP_BASE_PATH'] ?? $this->config['base_path']
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
        $app->instance('request', $request);
        $kernel->bootstrap();

        Facade::clearResolvedInstance('request');

        try {
            $response = (new Pipeline($app))
                ->send($request)
                ->through($app->shouldSkipMiddleware() ? [] : $kernel->middleware)
                ->then($kernel->dispatchToRouter());
        } catch (Exception $e) {
            $app[\App\Exceptions\Handler::class]->report($e);
            $response = $app[\App\Exceptions\Handler::class]->render($request, $e);
        } catch (Throwable $e) {
            $app[\App\Exceptions\Handler::class]->report($e = new FatalThrowableError($e));
            $response = $app[\App\Exceptions\Handler::class]->render($request, $e);
        }
        $app->flush();
        unset($app, $kernel);
        return $response;
    }

    private function log($message, $data = [])
    {
        if (!is_array($data)) {
            $data = [$data];
        }
        if ($this->config['log_enable']) {
//            app(SwooleLogger::class)->info("{$this->config['name']}: $message", $data);
        }
    }
}
