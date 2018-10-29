<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Socket Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the socket connections below you wish
    | to use as your default connection for all socket work.
    |
    */

    'default' => env('SOCKET_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Socket Connections
    |--------------------------------------------------------------------------
    |
    |
    */

    'connections' => [
        'default' => [
            'host' => env('SOCKET_HOST', '*.*.*.*'),
            'port' => env('SOCKET_PORT' ,'****'),
            'protocol' => env('SOCKET_PROTOCOL', 'tcp'),
        ],
        'udp' => [
            'host' => env('SOCKET_HOST', '*.*.*.*'),
            'port' => env('SOCKET_PORT' ,'****'),
            'protocol' =>  env('SOCKET_PROTOCOL', 'udp'),
        ]
    ]

];