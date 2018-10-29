<?php

namespace Wor\Sockets\Connectors;

interface SocketInterface
{

    /**
     * Socket constructor.
     *
     * @param $ip
     * @param $port
     * @param $protocol
     */
    public function __construct($ip, $port, $protocol);

    /**
     * Simple write to the socket
     *
     * @param string $message to send
     *
     * @return bool
     */
    public function write(string $message): bool;

    /**
     * Simple full read of socket contents
     *
     * @param int $timeout of stream
     *
     * @return bool|string
     */
    public function read($timeout = 1): string;
}