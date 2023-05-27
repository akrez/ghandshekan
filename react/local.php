<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

require __DIR__ . '/vendor/autoload.php';

$http = new HttpServer(function (ServerRequestInterface $request) {
    if ('CONNECT' === $request->getMethod()) {
        $response = new Response();
        $response->withStatus(200, 'Connection Established');
        return $response;
    }

    $guzzle = new Client();
    try {
        $guzzleResponse = $guzzle->request('POST', 'https://akrezing.ir/', ['body' => Message::toString($request)]);
    } catch (GuzzleHttp\Exception\ClientException $th) {
        $guzzleResponse = $th->getResponse();
    } catch (\Exception $th) {
        $guzzleResponse = new Response(500);
    }

    echo $request->getUri() . "\n";

    return Message::parseResponse($guzzleResponse->getBody()->getContents());
});

$socket = new SocketServer('127.0.0.1:8080');
$http->listen($socket);
