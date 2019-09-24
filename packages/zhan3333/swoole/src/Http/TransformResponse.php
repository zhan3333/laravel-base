<?php
/**
 * User: zhan
 * Date: 2019/9/24
 * Email: <grianchan@gmail.com>
 */

namespace Zhan3333\Swoole\Http;


use Illuminate\Http\Response;

class TransformResponse
{
    public static function handle(Response $transformResponse, \Swoole\Http\Response $response)
    {
        foreach ($transformResponse->headers as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $response->header($key, $value);
        }
        $response->status($transformResponse->getStatusCode());
    }
}
