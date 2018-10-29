<?php

namespace Wor\Sockets;

use Illuminate\Support\ServiceProvider;

class SocketsServiceProvider extends ServiceProvider {

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        $this->publishes([
            __DIR__ . '/../config/socket.php' => config_path('socket.php'),
        ], 'config');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->mergeConfigFrom(
            __DIR__.'/../config/socket.php', 'socket'
        );

        $this->app->singleton('sockets', function($app) {
            return new SocketsManager($app);
        });
    }

    public function provides() {
        return ['sockets'];
    }

}