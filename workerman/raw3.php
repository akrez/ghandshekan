<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request as Psr7Request;
use Nyholm\Psr7Server\ServerRequestCreator;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Workerman\Protocols\Http\Request as HttpRequest;

$akrez = json_decode(base64_decode($_SERVER['HTTP_AKREZ']), true);
$body = file_get_contents('php://input');

$r = new HttpRequest($body);
// $rr = GuzzleHttp\Psr7\Request $r;
$r->uri();

$request = new Request($r->method(), $akrez['uri'], $akrez['headers'], $body, $akrez['version']);

$guzzle = new Client();
$response =  $guzzle->send($r);

$emitter = new SapiEmitter();
$emitter->emit($response);
