<?php

use GuzzleHttp\Client;
use Workerman\Protocols\Http\Response as HttpResponse;
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
$worker = new Worker('http://127.0.0.1:443', $context);
// 6 processes
$worker->count = 6;
// Worker name.
$worker->name = 'php-http-proxy';


// Emitted when data received from client.
$worker->onMessage = function (Workerman\Connection\TcpConnection $connection, Workerman\Protocols\Http\Request $request) {

    echo $request->uri() . "\n";

    $serverScript = "http://shahab-tahrir.ir/raw.php";

    if ($request->method() === 'CONNECT') {
        // CONNECT.
        $response = new Response();
        $response = $response->withStatus(200, 'Connection Established');
        $connection->send($response);
    } else {
        // POST GET PUT DELETE etc.
        $guzzle = new Client();
        $response = $guzzle->request('POST', $serverScript, ['body' => $request->rawBuffer()]);

        if (true) {
            $responseBody = $response->getBody();
        }
        // $stringResponse = Workerman\Psr7\response_to_string($response);
        // file_put_contents('asd.txt', $stringResponse);
        // $connection->send($stringResponse, true);

        file_put_contents('dump/' . rand() . '.txt', $responseBody);
        $connection->send($responseBody, true);
    }
};

// Run.
Worker::runAll();
