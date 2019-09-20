<?php


namespace Zhan3333\Swoole\Job;


use Ramsey\Uuid\Uuid;
use Swoole\Client;
use Zhan3333\Swoole\Exceptions\JobRuntimeException;
use Zhan3333\Swoole\Exceptions\SwooleNotRunException;

class PendingDispatch
{
    /**
     * @var string job 的唯一id, finish回得到结果
     */
    public $jobId;
    /** @var Client */
    public $client;
    /** @var Job $job */
    public $job;
    /** @var 是否已经链接上服务器 */
    public $connected;
    private $dispatchNow = false;
    private $hasTryStartSwoole = false;
    private $needRetryStartSwoole = false;

    public function __construct(Job $job, $setting = [])
    {
        $this->needRetryStartSwoole = config('swoole.job.server_retry_start');
        $this->dispatchNow = $setting['dispatch_now'] ?? false;
        $this->job = $job;
        $this->setJobId(Uuid::uuid4()->toString());
        if (!$this->dispatchNow) {
            $this->connect();
        }
    }

    public function setJobId($taskId)
    {
        $this->jobId = $taskId;
        $this->job->jobId = $taskId;
    }

    private function connect()
    {
        try {
            $config = config('swoole.services.' . config('swoole.use_server'));
            $this->client = new Client(SWOOLE_SOCK_TCP);
            if (!$this->client->connect($config['host'], $config['port'], 0.5)) {
                throw new SwooleNotRunException("Swoole {$config['host']}:{$config['port']} not run");
            }
            $this->connected = true;
        } catch (\Exception $exception) {
            // 创建链接失败，尝试重连
            if ($this->needRetryStartSwoole && !$this->hasTryStartSwoole) {
                $this->hasTryStartSwoole = true;
                $this->tryStartSwoole();
                $this->connect();
            } else {
                $this->fail($exception);
            }
        }
    }

    private function tryStartSwoole()
    {
        app(SwooleManager::class)->start();
    }

    private function fail(\Exception $exception)
    {
        if (method_exists($this->job, 'failed')) {
            $this->job->failed($this->jobId, JobRuntimeException::copy($exception));
        }
    }

    private function send()
    {
        if ($this->dispatchNow) {
            try {
                $result = $this->job->handle(...Inject::getInjectParams($this->job, 'handle'));
                if (method_exists($this->job, 'finish')) {
                    $this->job->finish($this->jobId, $result);
                }
            } catch (\Exception $exception) {
                $this->fail($exception);
            }
        } else {
            if ($this->client) {
                $this->client->send(json_encode($this->getPayload()));
            }
        }
    }

    /**
     * 发送到task的data数据
     * @return array
     * @throws \ReflectionException
     */
    private function getPayLoad()
    {
        return [
            'id' => $this->jobId,
            'job' => [
                get_class($this->job),
                'handle',
            ],
            'data' => $this->getJobProperties(),
        ];
    }

    /**
     * 获取Job中的所有属性的 key=>value
     * @return array
     * @throws \ReflectionException
     */
    private function getJobProperties(): array
    {
        $class = new \ReflectionClass($this->job);
        $exceptNames = $this->job->exceptParamNames;
        $properties = $class->getProperties();
        $data = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            if (!in_array($name, $exceptNames, true)) {
                $data[$name] = $this->job->{$name};
            }
        }
        return $data;
    }

    public function __destruct()
    {
        if ($this->connected || $this->dispatchNow) {
            $this->send();
        }
    }
}
