<?php

use GuzzleHttp\Client;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http\Response as HttpResponse;
use \Workerman\Worker;
use Workerman\Psr7\Response;

require_once __DIR__ . '/vendor/autoload.php';

$context = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
];

$http_worker = new Worker('http://127.0.0.1:8086', $context);
$http_worker->count = 6;

$http_worker->onMessage = function (Workerman\Connection\TcpConnection $connection, Workerman\Protocols\Http\Request $request) {

    $serverScript = "http://shahab-tahrir.ir/raw3.php";

    if ($request->method() === 'CONNECT') {
        // CONNECT.
        $response = new Response();
        $response = $response->withStatus(200, 'Connection Established');
        $connection->send($response);
    } else {
        echo $request->uri() . "\n";
        // POST GET PUT DELETE etc.
        $guzzle = new Client();
        $response = $guzzle->request('POST', $serverScript, [
            'headers'  => [
                'akrez' => base64_encode(json_encode([
                    'method' => $request->method(),
                    'uri' => $request->uri(),
                    'headers' => explode("\r\n", $request->rawHead()),
                    'version' => $request->protocolVersion(),
                ]))
            ],
            'body' => $request->rawBody(),
        ]);
        $connection->send($response->getBody(), true);
    }
};

// Run all workers
Worker::runAll();
