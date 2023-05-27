<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;

require __DIR__ . '/vendor/autoload.php';

$request = @Message::parseRequest(file_get_contents('php://input'));
if ($request) {
    $guzzle = new Client();
    try {
        $guzzleResponse = $guzzle->send($request);
    } catch (ClientException $th) {
        $guzzleResponse = $th->getResponse();
    } catch (\Exception $th) {
        $guzzleResponse = new Response(500);
    }
    echo Message::toString($guzzleResponse);
}
