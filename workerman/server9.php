<?php
require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Protocols\Http\Response;

// SSL context.
$context = array(
    'http' => [
        'local_cert' => __DIR__ . '/localhost.pem',
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
        'verify_depth' => 0
    ]
);

// Create a Websocket server with ssl context.
$ws_worker = new Worker("http://localhost:8086", $context);

// Enable SSL. WebSocket+SSL means that Secure WebSocket (wss://). 
// The similar approaches for Https etc.
$ws_worker->transport = 'ssl';

$ws_worker->onMessage = function ($connection, $data) {
    // Send hello $data
    $connection->send('hello ' . $data);
};

$ws_worker->onConnect = function (Workerman\Connection\TcpConnection $connection) {

    echo $connection->getRecvBufferQueueSize();

    // CONNECT.
    $response = new Response();
    $response = $response->withStatus(200, 'Connection Established');
    $connection->send($response);
};

Worker::runAll();
