<?php


namespace Tests\Jobs;


use App\Services\AuthService;
use Zhan3333\Swoole\Exceptions\JobRuntimeException;
use Zhan3333\Swoole\Job\Dispatchable;
use Zhan3333\Swoole\Job\Job;

class PrintJobException extends Job
{
    use Dispatchable;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle(AuthService $authService)
    {
        $class = __CLASS__;
        \Log::debug("Job $class $this->jobId is run...", []);
        throw new \Exception('test exception');
    }

    public function finish($jobId, $result)
    {
        $class = __CLASS__;
        \Log::debug("Job $class: $jobId finish", [$result]);
    }

    public function failed($jobId, JobRuntimeException $exception)
    {
        $class = __CLASS__;
        \Log::debug("Job $class: $jobId failed", [$exception->getMessage()]);
    }
}
