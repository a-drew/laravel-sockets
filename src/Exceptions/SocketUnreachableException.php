<?php

namespace Wor\Sockets\Exceptions;

use Throwable;

class SocketUnreachableException extends SocketException
{
    public function __construct(string $message = 'Network Unreachable', int $code = 51, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}