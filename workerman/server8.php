<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Response as HttpResponse;
use Workerman\Worker;
use Workerman\Protocols\Http\Request as HttpRequest;

// Autoload.
require_once __DIR__ . '/vendor/autoload.php';

function parseRawHttpResponse($rawResponse, $filterHeaders = [])
{
    $headersAndBody = explode("\r\n\r\n", $rawResponse, 2) + [0 => null, 1 => null,];

    $status = 200;
    $headers = [];
    $version = '1.1';
    $reason = null;

    foreach (explode("\r\n", $headersAndBody[0]) as $headerStringIndex => $headerString) {
        if (0 === $headerStringIndex) {
            $header = explode(' ', $headerString, 3);
            $version = str_replace('http/', '', strtolower($header[0]));
            $status = $header[1];
            $reason = $header[2];
        } else {
            $headerKeyValue = explode(':', $headerString, 2);
            $headerKeyLower = strtolower($headerKeyValue[0]);
            $filter = in_array($headerKeyLower, $filterHeaders);

            if ('content-length' == $headerKeyLower) {
                $headerKey = 'Content-Length';
            } elseif ('content-type' == $headerKeyLower) {
                $headerKey = 'Content-Type';
            } elseif ('connection' == $headerKeyLower) {
                $headerKey = 'Connection';
            } elseif ('server' == $headerKeyLower) {
                $headerKey = 'Server';
            } else {
                $headerKey = $headerKeyValue[0];
            }

            if (!$filter) {
                $headers[$headerKey] = $headerKeyValue[1];
            }
        }
    }

    new Response($status, $headers, $headersAndBody[1], $version, $reason);
}

// SSL context.
$context = [
    'ssl' => [
        'local_cert'  => 'cert.pem',
        'local_pk'    => 'key.pem',
        'verify_peer' => false,
    ]
];


// Create a TCP worker.
$worker = new Worker('http://127.0.0.1:443', $context);
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

    $serverScript = "http://aliakbarrezaei.ir/agent/raw8.php";

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

        $headerFilters = [
            'connection',
            'transfer-encoding',
            'keep-alive',
            'x-encoded-content-encoding',
            'x-encoded-content-length',
            'content-length',
        ];

        $headers = [];
        foreach ($guzzleResponse->getHeaders() as $headerKey => $headerValues) {
            $filter = in_array(strtolower($headerKey), $headerFilters);

            $headerLower = strtolower($headerKey);

            $headerKey = $headerKey;
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
                $headers[$headerKey] = implode(';', $headerValues);
            }
        }

        echo "\n\n";
        var_dump($guzzleResponse->getHeaders());
        var_dump($headers);
        echo "\n\n";

        $response = new HttpResponse(
            $guzzleResponse->getStatusCode(),
            $headers,
            $guzzleResponse->getBody()->getContents()
        );
        $response->getReasonPhrase($guzzleResponse->getReasonPhrase());
        $response->withProtocolVersion($guzzleResponse->getProtocolVersion());
        $response->withHeader('Content-Length', $guzzleResponse->getBody()->getSize());

        $connection->send($response);
    }
};

// Run.
Worker::runAll();
