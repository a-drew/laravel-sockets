<?php

namespace Wor\Sockets;

class SocketsFacade extends \Illuminate\Support\Facades\Facade {

    protected static function getFacadeAccessor() {
        return 'sockets';
    }
}
