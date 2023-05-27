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
        // 'local_cert'  => './ssl_cert.pem',
        // 'local_pk'    => './ssl_key.pem',

        'allow_self_signed' => true,
        'verify_peer' => false,
        'verify_peer_name' => false,
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
$worker->transport = 'ssl';


// Emitted when data received from client.
$worker->onMessage = function (TcpConnection $connection, HttpRequest $request) {

    echo '---------------------------------' . "\r\n";
    echo $request->uri() . "\n";

    $serverScript = "http://localhost/req/raw6.php";

    if ('CONNECT' === $request->method()) {
        // CONNECT.
        $response = new Response();
        $response = $response->withStatus(200, 'Connection Established');
        $connection->send($response);
    } else {

        $guzzle = new Client();
        try {
            $guzzleResponse = $guzzle->request('POST', $serverScript, ['body' => $request->rawBuffer()]);
        } catch (GuzzleHttp\Exception\ClientException $th) {
            $guzzleResponse = $th->getResponse();
        } catch (\Exception $th) {
            $guzzleResponse = new Response(500);
        }

        echo '---------------------------------' . "\r\n\r\n";

        $rawResponse = $guzzleResponse->getBody()->getContents();

        $headersAndBody = explode("\r\n\r\n", $rawResponse, 2) + [0 => null, 1 => null,];

        $headers = [];
        $version = '1.1';
        $status = 200;
        $reason = '';
        foreach (explode("\r\n", $headersAndBody[0]) as $headerStringIndex => $headerString) {
            if (0 === $headerStringIndex) {
                $header = explode(" ", $headerString, 3);
                $version = str_replace('http/', '', strtolower($header[0]));
                $status = $header[1];
                $reason = $header[2];
            } else {
                $header = explode(":", $headerString, 2);

                $headerLower = strtolower($header[0]);

                $filter = in_array($headerLower, [
                    'connection',
                    'transfer-encoding',
                    'keep-alive',
                    'x-encoded-content-encoding',
                    'x-encoded-content-length',
                ]);

                $headerKey = $header[0];
                if ('content-length' == $headerLower) {
                    $headerKey = 'Content-Length';
                } elseif ('content-type' == $headerLower) {
                    $headerKey = 'Content-Type';
                } elseif ('connection' == $headerLower) {
                    $headerKey = 'Connection';
                } elseif ('server' == $headerLower) {
                    $headerKey = 'Server';
                }

                if (!$filter) {
                    $headers[$headerKey] = $header[1];
                }
            }
        }

        $response = new HttpResponse(
            $status,
            $headers,
            $headersAndBody[1]
        );
        $response->withProtocolVersion($version);
        if (empty($response->getHeader('content-type'))) {
            $response->withHeader('Content-Length', (string) \strlen($headersAndBody[1]));
        }

        var_dump($version);

        $connection->send($response);
    }
};

// Run.
Worker::runAll();
