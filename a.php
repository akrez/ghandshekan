<?php

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;

require __DIR__ . './vendor/autoload.php';

$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    if ('CONNECT' === $request->getMethod()) {
        return new Response(200, [], null, '1.1', 'Connection Established');
    }
    return new Response(200, [], Message::toString($request));
});

$uri = 'ssl://' . (isset($argv[1]) ? $argv[1] : '0.0.0.0:0');
$socket = new React\Socket\SocketServer($uri, array(
    // 'tls' => array(
    //     'local_cert' => isset($argv[2]) ? $argv[2] : __DIR__ . '/localhost.pem'
    // ),
    'ssl' => [
        'allow_self_signed' => true,
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
));
$http->listen($socket);

// $socket->on('connection', function (React\Socket\ConnectionInterface $connection) {
//     $connection->once('data', function ($requestAsString) use ($connection) {
//         $request = Message::parseRequest($requestAsString);
//         if ('CONNECT' === $request->getMethod()) {
//             $response = new Response(200, [], null, '1.1', 'Connection Established');
//             $connection->write(Message::toString($response));
//             echo $connection->getRemoteAddress();
//         }
//     });
// });

$socket->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});

echo 'Listening on ' . str_replace('tls:', 'https:', $socket->getAddress()) . PHP_EOL;
