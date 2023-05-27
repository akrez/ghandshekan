<?php

require __DIR__ . './vendor/autoload.php';

$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    return React\Http\Message\Response::html(
        "Hello World!\n"
    );
});

$uri = 'tls://' . (isset($argv[1]) ? $argv[1] : '0.0.0.0:0');
$uri = '0.0.0.0:8080';
$socket = new React\Socket\SocketServer($uri, array(
    // 'tls' => array(
    //     'local_cert' => isset($argv[2]) ? $argv[2] : __DIR__ . '/localhost.pem'
    // )
));
$http->listen($socket);

$socket->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});

echo 'Listening on ' . str_replace('tls:', 'https:', $socket->getAddress()) . PHP_EOL;
