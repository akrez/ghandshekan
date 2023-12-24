<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;

require('../vendor/autoload.php');
require('./Xxx.php');

$x = new Xxx('akrezing.ir', $_SERVER['SCRIPT_URI']);

$request = ServerRequest::fromGlobals()->withUri(new Uri($x->realUrl));

$client = new Client([
    'curl' => [
        CURLOPT_CONNECTTIMEOUT     => 10,
        CURLOPT_TIMEOUT            => 0,
        // don't bother with ssl
        CURLOPT_SSL_VERIFYPEER    => false,
        CURLOPT_SSL_VERIFYHOST    => false,
        // we will take care of redirects
        CURLOPT_FOLLOWLOCATION    => false,
        CURLOPT_AUTOREFERER        => false
    ]
]);

try {
    $response = $client->send($request);
} catch (GuzzleHttp\Exception\ClientException $e) {
    $response = $e->getResponse();
}

$cleanContentType = $x::getCleanContentType(implode(',', $response->getHeader('content-type')));
$isHtml = ('text/html' == $cleanContentType);

foreach ($response->getHeaders() as $headerKey => $headers) {
    header($headerKey . ':' . implode(',', $headers));
}

if (!$isHtml) {
    $bodyReader = $response->getBody();
    while (!$bodyReader->eof()) {
        echo $bodyReader->read(512);
        flush();
    }
} else {
    $body = $response->getBody()->getContents();
    echo $x->convertBody($body);
}
