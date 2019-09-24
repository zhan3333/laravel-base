<?php
/**
 * User: zhan
 * Date: 2019/9/24
 * Email: <grianchan@gmail.com>
 */

namespace Tests\Jobs;


use Zhan3333\Swoole\Job\Dispatchable;
use Zhan3333\Swoole\Job\Job;

class ReloadCommandTestJob extends Job
{
    use Dispatchable;

    public function __construct()
    {

    }

    public function handle()
    {
//        app()->bind(__CLASS__);
        \Log::debug('after reload11', [app()->has(__CLASS__)]);
//        app()->test = 'test';
    }

    public function finish()
    {

    }

    public function failed()
    {

    }
}
