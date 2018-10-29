<?php

namespace Wor\Sockets\Requests;


use Wor\Sockets\Connectors\SocketInterface as Socket;

class Request {

    /**
     * @var Socket
     */
    protected $socket;

    /**
     * @var string raw payload
     */
    protected $payload;

    /**
     * @var string final response object
     */
    protected $response;

    /**
     * Generic Request constructor.
     *
     * @param Socket $socket
     */
    public function __construct(Socket $socket)
    {
        $this->setSocket($socket);
    }

    /**
     * @param Socket $socket
     */
    public function setSocket(Socket $socket): void
    {
        $this->socket = $socket;
    }

    /**
     * @param string $payload
     */
    public function setPayload($payload): void
    {
        $this->payload = $payload;
    }

    /**
     * Return formatted response of request
     *
     * @return string
     */
    public function getResponse()
    {
        return new $this->response;
    }

    /**
     * Run the request on socket and return the response
     *
     * @return bool|mixed false if failed else return formatted response
     */
    public function make()
    {
        if($this->socket !== null && $this->socket->write($this->payload)) {
            $this->response = $this->socket->read();
            return $this->getResponse();
        }
        return false;
    }

    /**
     * make alias, meant to match eloquent syntax
     *
     * @return bool|mixed
     */
    public function get()
    {
        return $this->make();
    }
}