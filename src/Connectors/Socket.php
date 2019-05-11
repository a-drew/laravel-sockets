<?php

namespace Wor\Sockets\Connectors;

use Wor\Sockets\Exceptions\SocketUnreachableException;
use Wor\Sockets\Requests\Request;

class Socket
{

    /**
     * @var resource raw socket
     */
    protected $socket;

    /**
     * @var string remote connection uri
     */
    public $remote;

    /**
     * @var int last socket error number
     */
    protected $error_num;

    /**
     * @var string last socket error message
     */
    public $error_msg;

    /**
     * @var int length of the send or receiving message
     */
    protected $length = -1;

    /**
     * @var boolean
     */
    protected $debug;

    /**
     * Socket constructor.
     *
     * @param $ip
     * @param $port
     * @param $protocol
     */
    public function __construct($ip, $port, $protocol)
    {
        $ip = gethostbyname($ip);
        $this->remote = "$protocol://$ip:$port";
        $this->debug = env('SOCKET_DEBUG', false);
        $this->connect();
    }

    /**
     * Open a socket to the currently set remote
     *
     * @return bool success
     */
    public function connect(): bool
    {
        return $this->open();
    }

    /**
     * Connect directly to a given remote uri (proto://ip:port)
     *
     * @param string $remote to connect to
     *
     * @return bool
     */
    public function connectTo(string $remote): bool
    {
        $this->remote = $remote;
        return $this->connect();
    }

    /**
     * Close the socket
     *
     * @return bool successfully disconnected
     */
    public function disconnect(): bool
    {
        return $this->close();
    }

    /**
     * Reconnect the socket to the current remote
     *
     * @return bool successfully reconnected
     */
    public function reconnect(): bool
    {
        if($this->disconnect()) {
            return $this->connect();
        }
        return false; // disconnect failed
    }

    /**
     * Check if connection is established;
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        $client = $this->socket;
        return ($client !== null && $client !== false && stream_socket_get_name($client, true) !== false);
    }

    /**
     * Open a socket to the currently set remote
     *
     * @return bool successfully open
     * @throws SocketUnreachableException
     */
    public function open(): bool
    {
        try {
            $this->socket = stream_socket_client($this->remote,$this->error_num,$this->error_msg);
        } catch (\Exception $e) {
            throw new SocketUnreachableException($this->error_msg . ' | host: ' . $this->remote, $this->error_num, $e);
        }
        return $this->isConnected();
    }

    /**
     * Close the socket
     *
     * @return bool
     */
    public function close(): bool
    {
        if($this->socket !== null && $this->socket !== false) {
            fclose($this->socket);
            return true;
        }
        return false;
    }

    /**
     * Simple write to the socket
     *
     * @param string $message to send
     *
     * @return bool
     */
    public function write(string $message): bool
    {
        if($this->debug === true) { echo "\nsending message : $message \n"; }
        return fwrite($this->socket, $message, \strlen($message));
    }

    /**
     * Simple full read of socket contents
     *
     * @param int $timeout of stream
     *
     * @return bool|string
     */
    public function read($timeout = 1): string
    {
        if($this->debug === true) { echo "\nreceiving message : \n"; }
        stream_set_timeout($this->socket, $timeout);
        $read = stream_get_contents($this->socket, $this->length);
        if($this->debug === true) { echo $read."\n"; }
        return $read;
    }

    /**
     * Run a given request on top of this socket
     *
     * @param Request $request to run
     *
     * @return bool|mixed false if failed, formatted response if successful
     */
    public function makeRequest(Request $request)
    {
        $request->setSocket($this);
        return $request->make();
    }
}