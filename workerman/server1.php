<?php

use GuzzleHttp\Client;
use \Workerman\Worker;
use Workerman\Psr7\Response;

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
$worker = new Worker('tcp://127.0.0.1:8086', $context);
// 6 processes
$worker->count = 6;
// Worker name.
$worker->name = 'php-http-proxy';


// Emitted when data received from client.
$worker->onMessage = function (Workerman\Connection\TcpConnection $connection, $buffer) {

    $bufferRnrn = explode("\r\n\r\n", $buffer, 2);
    $bufferRnrn0Rn = explode("\r\n", $bufferRnrn[0]);
    $bufferRnrn0Rn0Space = explode(" ", $bufferRnrn0Rn[0]);

    if (3 == count($bufferRnrn0Rn0Space)) {

        $serverScript = "http://shahab-tahrir.ir/raw1.php";

        $originalAddress = $bufferRnrn0Rn0Space[1];
        $method = $bufferRnrn0Rn0Space[0];

        if ($method === 'CONNECT') {
            // CONNECT.
            $response = new Response();
            $response = $response->withStatus(200, 'Connection Established');
            $connection->send($response);
        } else {
            // POST GET PUT DELETE etc.
            $guzzle = new Client();
            $response = $guzzle->request('POST', $serverScript, ['body' => $buffer]);
            $connection->send($response->getBody(), true);
        }
    }
};

// Run.
Worker::runAll();
