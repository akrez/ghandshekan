<?php

use Workerman\Psr7\Response;

// Autoload.
require_once __DIR__ . '/vendor/autoload.php';

$buffer = file_get_contents('php://input');

$response = new Response(200, [], $buffer);

die(Workerman\Psr7\response_to_string($response));

die(strval($response));



function generateErrorMessage($message)
{
    $response = [
        "HTTP/1.1 200 OK",
        "Content-Type: text/html",
        "Content-Length: " . strlen($message),
    ];
    return "HTTP/1.1 200 OK\r\nContent-Length: " . strlen($message) . "\r\nContent-Type: text/html\r\n\r\n" . $message;

    return implode("\r\n", $response) . "\r\n\r\n" . $message;
}


die(generateErrorMessage('Hii'));
