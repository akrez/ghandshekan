<?php

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;

require __DIR__ . './vendor/autoload.php';

function console(...$data)
{
    echo implode(' ', $data) . PHP_EOL;
}

$socket = new React\Socket\SocketServer(isset($argv[1]) ? $argv[1] : '127.0.0.1:0', [
    'tls' => array(
        'local_cert' => isset($argv[2]) ? $argv[2] : (__DIR__ . '/localhost.pem')
    ),
]);
//
$socket->on('connection', function (React\Socket\ConnectionInterface $connection) {
    //
    console("\nOO", $connection->getRemoteAddress());
    //
    $connection->once('data', function ($requestAsString) use ($connection) {
        //
        // echo $requestAsString;
        $request = null;
        try {
        } catch (\Throwable $th) {
        }
        $request = Message::parseRequest($requestAsString);
        file_put_contents('err', $requestAsString);
        //
        $response = null;
        if ($request) {

            if ('CONNECT' === $request->getMethod()) {
                $response = new Response(200, [], null, '1.1', 'Connection Established');
            } else {
                $host = $request->getUri()->getHost();
                //
                if ($request->getUri()->getPort()) {
                    $port = $request->getUri()->getPort();
                } elseif ('https' == $request->getUri()->getScheme()) {
                    $port = 443;
                } else {
                    $port = 80;
                }
                //
                $socket = fsockopen(
                    $host,
                    $port,
                    $errno,
                    $error
                );
                //
                console('->', $host, $port,);
                if ($socket) {
                    fwrite($socket, $requestAsString);
                    while (!feof($socket)) {
                        $line = fgets($socket);
                        $connection->write($line);
                    }
                    fclose($socket);
                }
                console('<-', $host, $port,);
                //
            }
        }
        echo Message::toString($response);
        $connection->end($response ? Message::toString($response) : null);
    });
    //
    $connection->on('close', function () use ($connection) {
        console('XX', $connection->getRemoteAddress(),);
    });
});

console(strtr($socket->getAddress(), ['tcp:' => 'http:', 'tls:' => 'https:']), 'listening');
