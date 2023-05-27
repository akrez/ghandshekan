<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Workerman\Protocols\Http\Request as HttpRequest;
use Workerman\Psr7\Response;

function logg($stime, $t, $c = '')
{
    file_put_contents('dump/' . $stime . '-' . $t . '-' . (time() - $stime), $c);
}

$stime = time();
logg($stime, 0);

$buffer = file_get_contents('php://input');
logg($stime, 1);

$workermanRequest = new HttpRequest($buffer);
logg($stime, 2);

$request = new Request(
    $workermanRequest->method(),
    $workermanRequest->uri(),
    $workermanRequest->header(),
    $workermanRequest->rawBody(),
    $workermanRequest->protocolVersion()
);
logg($stime, 3);

$guzzle = new Client();
$response = $guzzle->send($request);
logg($stime, 4);

if (false) {
    $emitter = new SapiEmitter();
    $emitter->emit($response);
} elseif (false) {
    $stringResponse = Workerman\Psr7\response_to_string($response);
    $response = new Response(200, [], $stringResponse);
    die(strval($response));
} elseif (false) {
    $stringResponse = Workerman\Psr7\response_to_string($response);
    die($stringResponse);
} else {
    $response = new Response(
        $response->getStatusCode(),
        $response->getHeaders(),
        json_encode(   $response->getHeaders()),
        $response->getBody(),
        $response->getProtocolVersion(),
        $response->getReasonPhrase(),
    );
    die(strval($response));
}
