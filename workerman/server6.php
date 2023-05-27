<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Response as HttpResponse;
use Workerman\Worker;
use Workerman\Psr7\Response as WorkermanResponse;
use Workerman\Protocols\Http\Request as HttpRequest;

// Autoload.
require_once __DIR__ . '/vendor/autoload.php';


// SSL context.
$context = [
    'ssl' => [
        // 'local_cert'  => 'cacert.pem',
        'verify_peer' => false,
        'verify_peer_name' => false,
        // 'allow_self_signed' => true,
    ]
];


// Create a TCP worker.
$worker = new Worker('http://127.0.0.1:8086', $context);
// 6 processes
$worker->count = 6;
// Worker name.
$worker->name = 'php-http-proxy';
// Enable SSL. WebSocket+SSL means that Secure WebSocket (wss://). 
// The similar approaches for Https etc.
// $worker->transport = 'ssl';


// Emitted when data received from client.
$worker->onMessage = function (TcpConnection $connection, HttpRequest $request) {

    // echo $request->uri() . "\n";

    $serverScript = "http://aliakbarrezaei.ir/agent/";
    $serverScript = "http://localhost/req/raw6.php";

    $status = 200;
    $headers = [];
    $body = null;
    $version = '1.1';
    $reason = 'Connection Established';

    if ('CONNECT' !== $request->method()) {
        $guzzle = new Client();
        $guzzleResponse = $guzzle->request('POST', $serverScript, ['body' => $request->rawBuffer()]);

        $rawResponse = $guzzleResponse->getBody()->getContents();
        file_put_contents('res.txt', $rawResponse);

        $headersAndBody = explode("\r\n\r\n", $rawResponse, 2) + [0 => null, 1 => null,];

        foreach (explode("\r\n", $headersAndBody[0]) as $headerStringIndex => $headerString) {
            if (0 === $headerStringIndex) {
                $header = explode(" ", $headerString, 3);
                $version = $header[0];
                $status = $header[1];
                $reason = $header[2];
                if (!is_numeric($status)) {
                    echo $headerString;
                }
            } else {
                $header = explode(":", $headerString, 2);
                $filter = in_array(strtolower($header[0]), [
                    'connection',
                    'transfer-encoding',
                    'content-length',
                ]);
                if (!$filter) {
                    // $headers[$header[0]] = $header[1];
                }

                if ('content-type' === strtolower($header[0])) {
                    $headers[$header[0]] = $header[1];
                    var_dump($header);
                }
            }
        }

        $body = $headersAndBody[1];
    }

    $response = new WorkermanResponse(
        $status,
        $headers,
        $body,
        $version,
        $reason
    );
    $response = $response->withHeader('Content-Length', (string) $response->getBody()->getSize());
    $connection->send(Message::toString(new Response(
        $status,
        $response->getHeaders(),
        $body,
        $version,
        $reason
    )), true);
};

// Run.
Worker::runAll();
