# About laravel-sockets

This project is a good place to start for developing an application that is requires 
to retrieve or send data via UDP/TCP Sockets .

## Requirements

 - PHP >= 7.0
 - Laravel >= 5.5 

## Quick Install
```
$ composer require windofrussia/laravel-sockets:"1.0"
```
OR 

add `windofrussia/laravel-sockets:"1.0"` to your `require` list in the `composer.json` file.

#### Service Provider & Facade (Optional on Laravel 5.5+)
Register provider and facade on your config/app.php file.
```
'providers' => [
    ...,
    Wor\Sockets\SocketsServiceProvider::class,
]

'aliases' => [
    ...,
    'Sockets' => Wor\Sockets\SocketsFacade::class,
]
```

#### Configuration (Optional)
```
$ php artisan vendor:publish --provider="Wor\Sockets\SocketsServiceProvider"
```

## Debugging Mode
To enable debugging mode, just set SOCKET_DEBUG=true and the package will echo the raw payload being sent and received across Sockets.

IMPORTANT: Please make sure that SOCKET_DEBUG is set to false when your app is on production.

## Usage
### Basic Usage

1. Add the default socket remote to your .env file
    ```
    ...
    SOCKET_CONNECTION=default
    SOCKET_HOST=<YOUR IP HERE>
    SOCKET_PORT=<YOUR PORT HERE>
    SOCKET_PROTOCOL=tcp
    SOCKET_DEBUG=true
    ...
    ```

2. In Laravel or during a tinker session
    ```
    Sockets::write('Message');
    $response = Sockets::read(); //returns the string response from the socket.
    ```

### Intended Usage
This package is intended to be extended. There are two basic concepts at play here : Sockets & Requests.

#### Sockets
Sockets are the basic communication layer. They should are meant to be extended to 
include any frame wrapping or encoding that you may want to use or that the receiving 
service expects. 

They should extend the base Socket class and implement the SocketInterface. 

In short you should implement the read & write methods to match your expected I/O.

```
<?php

namespace Wor\Salto\Connectors;

use Wor\Sockets\Connectors\Socket;
use Wor\Sockets\Connectors\SocketInterface;

class SaltoSocket extends Socket implements SocketInterface
{
    /**
     * @var string protocol identifier
     */
    public $protocol = 'STP';

    /**
     * @var string version of the salto protocol
     */
    public $version = '00';

    /**
     * Craft the ship packet header
     * @return string header
     */
    public function getHeader()
    {
        return "$this->protocol/$this->version/$this->length/";
    }

    /**
     * Salto SHIP protocol based write method   **overloads base write() method**
     * Wraps the message in the expected xml format and creates
     *
     * @param string $message to send
     *
     * @return bool success
     */
    public function write(string $message): bool
    {
        $data = '<?xml version="1.0" encoding="ISO-8859-1"?><RequestCall>'.$message.'</RequestCall>';
        $this->length = \strlen($data); //setups the length displayed in the header
        return parent::write($this->getHeader().$data);
    }

    /**
     * Salto SHIP protocol based read method   **overloads base write() method**
     * Reads header and uses expected message length to quickly load the response
     *
     * @param int $timeout in seconds
     *
     * @return string|null received message
     */
    public function read($timeout = 25): string
    {
        $this->protocol = stream_get_line($this->socket,4, '/');
        $this->version = stream_get_line($this->socket,3, '/');
        $this->length = (int) stream_get_line($this->socket,8, '/');

        stream_set_timeout($this->socket, $timeout);
        $read = stream_get_contents($this->socket, $this->length);
        return $read;
    }
}
```



#### Requests
Requests are meant to encapsulate an action that is performed. They should match the endpoints
provided by the service you're working with. Think of them as database transactions. Once you
setup the payload and make the request it performs a write and read on the provided socket and
returns a parsed result. 

Requests extend the base Request class and implement the RequestInterface.

```
<?php

namespace Wor\Salto\Requests;

use Wor\Salto\Connectors\SaltoSocket as Socket;
use Wor\Salto\Exceptions\EncoderConnectionException;
use Wor\Salto\Exceptions\EncoderTimeoutException;
use Wor\Salto\Exceptions\XMLDocumentException;
use Wor\Salto\Exceptions\OperationNotSupportedException;
use Wor\Salto\Exceptions\SaltoException;
use Wor\Sockets\Requests\Request;
use Wor\Sockets\Requests\RequestInterface;
use Illuminate\Support\Collection;

class SaltoRequest extends Request implements RequestInterface {

    /**
     * @var string ship protocol RequestName
     */
    protected $name;

    /**
     * @var array of parameters
     */
    protected $params = [];

    /**
     * @var string json encoded response
     */
    protected $json;

    /**
     * @var Collection final response object
     */
    protected $collection;

    /**
     * Salto Request constructor.
     *
     * @param Socket        $socket
     * @param string|array  $params
     * @param string        $name
     */
    public function __construct(Socket $socket, $params = [], $name = null)
    {
        $this->boot();
        parent::__construct($socket);

        if (\is_array($params)) {
            $this->setParams($params);
        } elseif (\is_string($params)) {
            // Allow to ignore parameters array and pass the name of the request directly
            $name = $params;
        }
        if($name !== null) { // Avoid overriding self defined default request name
            $this->setName($name);
        }
        $this->beforePayload();
        $this->prepPayload();
    }

    /**
     * Generic boot method to be changed in subclasses
     * ran before the rest of the constructor
     */
    public function boot(): void
    {
        //
    }

    /**
     * Generic boot method to be changed in subclasses
     * ran before setting up the payload
     */
    public function beforePayload(): void
    {
        //
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @param array $params
     */
    public function setParams($params): void
    {
        $this->params = $params;
    }

    /**
     * Return formatted response of request
     *
     * @return Collection|mixed
     */
    public function getResponse()
    {
        //$this->checkException();
        return new Collection($this->collection);
    }

    /**
     * Parse name and params into xml payload
     */
    private function prepPayload(): void
    {
        $header = '<RequestName>'.$this->name.'</RequestName>';
        $params = '<Params>';

        foreach ($this->params as $key => $value) {
            if ($key !== null && $value !== null) {
                // cast boolean values to int for output to ship xml
                !\is_bool($value) ?: $value = (int) $value;

                // Output associative array as an ship parameter entry
                $params .= '<'.$key.'>'.$value.'</'.$key.'>';
            }
        }
        $this->payload = $header.$params.'</Params>';
    }

    /**
     * Run the request on socket and return the response
     *
     * @return bool|mixed false if failed else return formatted response
     *
     * @throws SaltoException
     */
    public function make()
    {
        if($this->socket !== null && $this->socket->write($this->payload)) {
            $this->response = $this->socket->read();
            $xml = simplexml_load_string($this->response);
            $this->json = json_encode($xml);
            $this->collection = collect(json_decode($this->json, true));
            $this->checkException();
            return $this->getResponse();
        }
        return false;
    }

    /**
     * Check the response for known ship protocol exceptions
     *
     * @return bool
     *
     * @throws SaltoException
     */
    public function checkException(): bool
    {
        if($this->collection->has('Exception')) {
            $exception = $this->collection['Exception'];

            switch ( (int) $exception['Code']) {
                case 4:
                    throw new OperationNotSupportedException($exception['Message']);
                    break;
                case 12:
                    throw new XMLDocumentException($exception['Message']);
                    break;
                case 401:
                    throw new EncoderConnectionException($exception['Message']);
                    break;
                case 403:
                    throw new EncoderTimeoutException($exception['Message']);
                    break;
                default:
                    throw new SaltoException($exception['Message'], $exception['Code']);
                    break;
            }
        }
        return false;
    }
}
```

Then you are free to instantiate new requests and run them against your socket service.

```
$socket = Socket::socket('default');
$params = []; //the parameters you want to send through
$request = new SaltoRequest($socket, $params, 'GetInfo');
$response = $request->get();
```

In fact you can take it one step further and create action requests based on your new genetic service request.

```
$socket = Socket::socket('default');
$params = []; //the parameters you want to send through
$request = new GetInfoRequest($socket, $params);
$response = $request->get();
```
```
<?php

namespace Wor\Salto\Requests;

use Wor\Salto\Requests\SaltoRequest;

class GetInfoRequest extends SaltoRequest {

        protected $name = 'GetInfo'; //overload the default name with the request type
    
        public function getResponse() //overload the default getResponse action.
        {
            $response = parent::getResponse();
            //Manipulate the response collection as you see fit. maybe match to domain models..
            return $response;
        }
}
```

### Any Issues?
If you discover any errors feel free to report them in the issue tracker.