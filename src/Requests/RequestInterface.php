<?php

namespace Wor\Sockets\Requests;

interface RequestInterface
{
    /**
     * Return formatted response of request
     */
    public function getResponse();

    /**
     * Run the request on socket and return the response
     *
     * @return bool|mixed false if failed else return formatted response
     */
    public function make();
}