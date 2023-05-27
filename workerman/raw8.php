<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

// Autoload.
require_once __DIR__ . '/vendor/autoload.php';

function parseRawHttpRequest($rawRequest, $filterHeaders = []): GuzzleHttp\Psr7\Request
{
    $headersAndBody = explode("\r\n\r\n", $rawRequest, 2) + [0 => null, 1 => null,];

    $method = 'GET';
    $uri = '';
    $headers = [];
    $version = '1.1';

    foreach (explode("\r\n", $headersAndBody[0]) as $headerStringIndex => $headerString) {
        if (0 === $headerStringIndex) {
            $header = explode(' ', $headerString, 3);
            $method = $header[0];
            $uri = $header[1];
            $version = str_replace('http/', '', strtolower($header[2]));
        } else {
            $headerKeyValue = explode(':', $headerString, 2);
            $headerKeyLower = strtolower($headerKeyValue[0]);
            $filter = in_array($headerKeyLower, $filterHeaders);
            if (!$filter) {
                $headers[$headerKeyValue[0]] = $headerKeyValue[1];
            }
        }
    }

    return new Request($method, $uri, $headers, $headersAndBody[1], $version);
}

if (true) {
    $buffer = file_get_contents('request.txt');
} else {
    $buffer = file_get_contents('php://input');
}

$request = parseRawHttpRequest($buffer);

$guzzle = new Client();
try {
    $response = $guzzle->send($request);
} catch (GuzzleHttp\Exception\ClientException $th) {
    $response = $th->getResponse();
}

$headerFilters = [
    'connection',
    'transfer-encoding',
    'keep-alive',
    'x-encoded-content-encoding',
    'x-encoded-content-length',
    'content-length',
];

$response->withAddedHeader('Content-Length', $response->getBody()->getSize());

header_remove();
header(implode(' ', [$response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()]));
foreach ($response->getHeaders() as $headerKey => $headerValues) {
    if (!in_array(strtolower($headerKey), $headerFilters)) {
        foreach ($headerValues as $headerValue) {
            header($headerKey . ':' . $headerValue);
        }
    }
}
echo $response->getBody();
