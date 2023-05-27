<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\MessageInterface;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response;
use Workerman\Psr7\Response as WorkermanResponse;

require_once __DIR__ . '/vendor/autoload.php';

if (false) {
    $buffer = file_get_contents('request.txt');
} else {
    $buffer = file_get_contents('php://input');
}

$workermanRequest = new WorkermanRequest($buffer);

$request = new Psr7Request(
    $workermanRequest->method(),
    $workermanRequest->uri(),
    $workermanRequest->header(),
    $workermanRequest->rawBody(),
    $workermanRequest->protocolVersion()
);

$guzzle = new Client();
try {
    $response = $guzzle->send($request);
} catch (GuzzleHttp\Exception\ClientException $th) {
    $response = $th->getResponse();
}

header_remove();
foreach ($response->getHeaders() as $headerKey => $headerValues) {
    $filter = in_array(strtolower($headerKey), [
        // 'connection',
        // 'transfer-encoding',
        // 'content-length',
        // 'keep-alive',
    ]);
    if (!$filter) {
        foreach ($headerValues as $headerValue) {
            header($headerKey . ':' . $headerValue);
        }
    }
}
header('Content-Length' . ':' . $response->getBody()->getSize());
header('akrez' . ':' . 'test');

echo $response->getBody();
