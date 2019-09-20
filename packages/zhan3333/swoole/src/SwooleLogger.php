<?php
/**
 * User: zhan
 * Date: 2019/9/20
 * Email: <grianchan@gmail.com>
 */

namespace Zhan3333\Swoole;

/**
 * Class SwooleLogger|LogManager
 * @package Zhan3333\RabbitMQ
 */
class SwooleLogger
{
    public static $logger;

    public function __construct($logger = null)
    {
        self::$logger = $logger;
    }

    public static function __callStatic($method, $args)
    {
        if (self::$logger) {
            self::$logger->{$method}(...$args);
        }
    }

    public function __call($method, $args)
    {
        if (self::$logger) {
            self::$logger->{$method}(...$args);
        }
    }
}
