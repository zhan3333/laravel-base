<?php


namespace Tests\Unit;


use Swoole\Client;
use Tests\Jobs\PrintJob;
use Tests\Jobs\PrintJobException;
use Tests\Jobs\PrintJobNow;
use Tests\TestCase;

class SwooleTest extends TestCase
{
    /**
     * 测试发送数据到swoole
     */
    public function noTestSendDate()
    {
        $client = new Client(SWOOLE_SOCK_TCP);
        //连接到服务器
        $this->assertTrue((bool)$client->connect(config('swoole.default.host'), config('swoole.default.port'), 0.5));
        //向服务器发送数据
        $this->assertTrue((bool)$client->send('hello world'));
        //从服务器接收数据
        $data = $client->recv();
        $this->assertTrue((bool)$data);
        //关闭连接
        $client->close();
    }

    public function testJobDispatch()
    {
        $jobId = PrintJob::dispatch('test message')->jobId;
        $this->assertInternalType('string', $jobId);

        $jobId = '123456789';
        PrintJob::dispatch('test set job id')->setJobId($jobId);
    }

    public function testJobDispatchException()
    {
        $jobId = PrintJobException::dispatch('test message')->jobId;
        $this->assertInternalType('string', $jobId);
    }

    public function testJobDispatchNow()
    {
        $jobId = PrintJobNow::dispatchNow('test message')->jobId;
        $this->assertInternalType('string', $jobId);
    }
}
