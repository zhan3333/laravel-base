<?php
/**
 * User: zhan
 * Date: 2019/9/25
 * Email: <grianchan@gmail.com>
 */

namespace Zhan3333\Swoole\Http;


use Illuminate\Foundation\Http\Kernel as HttpKernel;

class SwooleKernel extends HttpKernel
{
    public $middleware;

    public function dispatchToRouter()
    {
        return parent::dispatchToRouter();
    }
}
