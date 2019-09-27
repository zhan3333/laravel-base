<?php

return [
    'use_server' => 'default',
    'services' => [
        'default' => [
            'log_enable' => true,
            'log_channel' => 'single',
            'name' => 'default-swoole',
            'handle_class' => \Zhan3333\Swoole\Swoole::class,
            'host' => '0.0.0.0',
            'port' => 8888,
            'timeout' => 1,
            'retry' => 1,
            'worker_num' => 1, // 一般设置为服务器CPU数的1-4倍
            'daemonize' => true,  // 以守护进程执行
            'max_request' => 10000,
            'dispatch_mode' => 2,
            'task_worker_num' => 1,
            'task_ipc_mode' => 3, // 使用消息队列，争抢模式
            'log_file' => storage_path('logs/default-swoole.log'),
            'pid_file' => storage_path('pid/default-swoole.pid'),
        ],
    ],
    'job' => [
        'server_retry_start' => true,
    ],
];
