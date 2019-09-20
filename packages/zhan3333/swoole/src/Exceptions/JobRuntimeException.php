<?php


namespace Zhan3333\Swoole\Exceptions;


class JobRuntimeException extends \RuntimeException
{
    public static function copy(\Exception $exception)
    {
        $instant = new self($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        $instant->file = $exception->getFile();
        $instant->line = $exception->getLine();
        return $instant;
    }
}
