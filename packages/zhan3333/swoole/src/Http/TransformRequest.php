<?php
/**
 * User: zhan
 * Date: 2019/9/24
 * Email: <grianchan@gmail.com>
 */

namespace Zhan3333\Swoole\Http;


use Illuminate\Http\Request;

class TransformRequest
{
    public static function handle(\Swoole\Http\Request $request): Request
    {
        $header = $request->header;
        $server = static::transformServerParameters($request->server ?? [], $header);
        $baseRequest = new \Symfony\Component\HttpFoundation\Request(
            $request->get ?? [],
            $request->post ?? [],
            [],
            $request->cookie ?? [],
            $request->files ?? [],
            $server,
            $request->rawContent()
        );
        return Request::createFromBase($baseRequest);
    }

    protected static function transformServerParameters(array $server, array $header)
    {
        $__SERVER = [];

        foreach ($server as $key => $value) {
            $key = strtoupper($key);
            $__SERVER[$key] = $value;
        }

        foreach ($header as $key => $value) {
            $key = str_replace('-', '_', $key);
            $key = strtoupper($key);

            if (!in_array($key, ['REMOTE_ADDR', 'SERVER_PORT', 'HTTPS'])) {
                $key = 'HTTP_' . $key;
            }

            $__SERVER[$key] = $value;
        }

        return $__SERVER;
    }
}
