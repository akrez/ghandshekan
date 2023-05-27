<?php

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request as Psr7Request;
use \Workerman\Worker;
use Workerman\Psr7\Response;
use \Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

// Autoload.
require_once __DIR__ . '/vendor/autoload.php';


// SSL context.
$context = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
];


// Create a TCP worker.
$worker = new Worker('http://127.0.0.1:443', $context);
// 6 processes
$worker->count = 6;
// Worker name.
$worker->name = 'php-http-proxy';


// Emitted when data received from client.
$worker->onMessage = function (TcpConnection $connection, Request $request) {

    echo $request->uri() . "\n";

    $remoteConnection = new AsyncTcpConnection('tcp://shahab-tahrir.ir:80');

    if ($request->method() === 'CONNECT') {
        // CONNECT.
        $response = new Response();
        $response = $response->withStatus(200, 'Connection Established');
        $connection->send($response);
    } else {

        $newRequestString = Message::toString(new Psr7Request(
            'POST',
            'http://shahab-tahrir.ir/raw5.php',
            [],
            $request->rawBuffer()
        ));

        file_put_contents(rand() . '.txt', $newRequestString);

        // POST GET PUT DELETE etc.
        $remoteConnection->send($newRequestString);
    }

    // Pipe.
    $remoteConnection->pipe($connection);
    $connection->pipe($remoteConnection);
    $remoteConnection->connect();
};

// Run.
Worker::runAll();
