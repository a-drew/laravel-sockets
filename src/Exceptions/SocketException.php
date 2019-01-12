<?php

namespace Wor\Sockets\Exceptions;

use Exception;
use Throwable;

class SocketException extends Exception
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct('[SOCK'.$code.'] ' . $message, $code, $previous);
    }
}