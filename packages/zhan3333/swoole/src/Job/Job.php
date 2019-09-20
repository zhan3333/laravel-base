<?php


namespace Zhan3333\Swoole\Job;

class Job
{
    /**
     * 唯一键, 只有在
     * @var string
     */
    public $jobId;

    /**
     * 不会加载到task data 中的属性
     * @var array
     */
    public $exceptParamNames = [
        'jobId',
        'exceptParamNames',
    ];
}
