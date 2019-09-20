<?php
/**
 * User: zhan
 * Date: 2019/9/20
 * Email: <grianchan@gmail.com>
 */

namespace Zhan3333\Swoole\Facades;


use Illuminate\Support\Facades\Facade;
use Zhan3333\Swoole\SwooleLogger;

class SwooleLog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SwooleLogger::class;
    }
}
