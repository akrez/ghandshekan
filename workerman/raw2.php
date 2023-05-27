<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17Factory, // ServerRequestFactory
    $psr17Factory, // UriFactory
    $psr17Factory, // UploadedFileFactory
    $psr17Factory  // StreamFactory
);
$request = $creator->fromGlobals();

$akrezUrls = $request->getHeader('akrez-url');
$akrezUrl = base64_decode($akrezUrls[0]);
$request->withoutHeader('akrez-url');
$request->withUri(new Uri($akrezUrl));

if (false) {
    $guzzle = new Client();
    $response =  $guzzle->send($request);
} else {
    $buffer = file_get_contents('php://input');
    $response = new Response(200, [], json_encode([$request->getHeaders()]));
}

$emitter = new SapiEmitter();
$emitter->emit($response);
