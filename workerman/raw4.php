<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Psr7\Response as WorkermanResponse;
// use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
// use Narrowspark\HttpEmitter\SapiEmitter;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/SapiEmitter.php';

$buffer = file_get_contents('php://input');

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

$emitter = new SapiEmitter();
$emitter->emit($response);