<?php
/**
 * User: zhan
 * Date: 2019/9/25
 * Email: <grianchan@gmail.com>
 */

namespace Zhan3333\Swoole\Http;


use Exception;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class SwooleKernel extends HttpKernel
{
    /**
     * Handle an incoming HTTP request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handle($request)
    {
        try {
            $request->enableHttpMethodParameterOverride();

            $response = $this->sendRequestThroughRouter($request);
        } catch (Exception $e) {
            $this->reportException($e);

            $response = $this->renderException($request, $e);
        } catch (Throwable $e) {
            $this->reportException($e = new FatalThrowableError($e));

            $response = $this->renderException($request, $e);
        }

        $this->app['events']->dispatch(
            new Events\RequestHandled($request, $response)
        );

        return $response;
    }

    /**
     * Send the given request through the middleware / router.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function sendRequestThroughRouter($request)
    {
        $this->app->instance('request', $request);

        Facade::clearResolvedInstance('request');

        $this->bootstrap();

        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
            ->then($this->dispatchToRouter());
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Get the route dispatcher callback.
     *
     * @return \Closure
     */
    public function dispatchToRouter()
    {
        return function ($request) {
            $this->app->instance('request', $request);

            return $this->router->dispatch($request);
        };
    }
}
