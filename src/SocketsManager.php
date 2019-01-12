<?php

namespace Wor\Sockets;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Wor\Sockets\Connectors\Socket as ConcreteSocket;
use Wor\Sockets\Connectors\SocketInterface as Socket;

/**
 * Class SocketManager
 *
 * This class is the package entry and maintains all active socket connections.
 * Use it in a similar manner to the laravel DB facade and call methods from API class.
 */
class SocketsManager
{

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var array[Socket] of active socket connections
     */
    protected $sockets = [];

    /**
     * SocketManager constructor
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Creates a new socket instance based on the
     *
     * @param string $name name in socket configuration
     *
     * @return ConcreteSocket socket
     */
    public function makeSocket(string $name) {
        $config = $this->configuration($name);
        //TODO: replace with factory
        return new ConcreteSocket($config['host'],$config['port'],$config['protocol']);
    }

    /**
     * Connection alias
     *
     * @param string $name
     * @return Socket
     */
    public function socket($name = null) {
        return $this->connection($name);
    }

    /**
     * Get a socket connection instance.
     *
     * @param  string  $name
     * @return Socket
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->getDefaultSocket();

        if (! isset($this->sockets[$name])) {
            $this->sockets[$name] = $this->makeSocket($name);
        }

        return $this->sockets[$name];
    }

    /**
     * Get the configuration for a socket.
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        $name = $name ?: $this->getDefaultSocket();

        // To get the socket connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = $this->app['config']['socket.connections'];

        if (($config = Arr::get($connections, $name)) === null) {
            throw new InvalidArgumentException("Socket [{$name}] not configured.");
        }

        return $config;
    }

    /**
     * Disconnect from the given socket and remove from local cache.
     *
     * @param  string  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name = $name ?: $this->getDefaultSocket();

        $this->disconnect($name);

        unset($this->sockets[$name]);
    }

    /**
     * Disconnect from the given socket.
     *
     * @param  string  $name
     * @return void
     */
    public function disconnect($name = null)
    {
        if (isset($this->sockets[$name = $name ?: $this->getDefaultSocket()])) {
            $this->sockets[$name]->close();
        }
    }

    /**
     * Reconnect to the given socket.
     *
     * @param  string  $name
     * @return Socket
     */
    public function reconnect($name = null)
    {
        $this->disconnect($name = $name ?: $this->getDefaultSocket());

        if (! isset($this->sockets[$name])) {
            return $this->connection($name);
        }

        return $this->sockets[$name] = $this->makeSocket($name);
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultSocket()
    {
        return $this->app['config']['socket.default'];
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultSocket($name)
    {
        $this->app['config']['socket.default'] = $name;
    }

    /**
     * Return all of the created connections.
     *
     * @return array
     */
    public function getSockets()
    {
        return $this->sockets;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}