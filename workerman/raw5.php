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
require_once __DIR__ . '/SapiEmitter.php';

if (true) {
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

function getHeaderArray($message, $skipHeaderKeys = [])
{
    $msg = [];
    if ($message instanceof Psr7Request) {
        $msg[] = trim($message->getMethod() . ' '
            . $message->getRequestTarget())
            . ' HTTP/' . $message->getProtocolVersion();
        if (!$message->hasHeader('host')) {
            $msg[] = "\r\nHost: " . $message->getUri()->getHost();
        }
    } elseif ($message instanceof Psr7Response) {
        $msg[] = 'HTTP/' . $message->getProtocolVersion() . ' '
            . $message->getStatusCode() . ' '
            . $message->getReasonPhrase();
    } else {
        throw new \InvalidArgumentException('Unknown message type');
    }

    foreach ($message->getHeaders() as $name => $values) {
        if (!in_array(strtolower($name), $skipHeaderKeys)) {
            if (strtolower($name) === 'set-cookie') {
                foreach ($values as $value) {
                    $msg[] = "{$name}: " . $value;
                }
            } else {
                $msg[] = "{$name}: " . implode(', ', $values);
            }
        }
    }

    return $msg;
}

if (headers_sent()) {
    \headers_sent($filename, $line);
    $msg = "Headers already sent in {$filename} on line {$line}\n"
        . "Emitter can't send headers once the headers block has already been sent.";
    throw new Exception($msg, 500);
}
header_remove();
$headers = getHeaderArray($response, ['transfer-encoding']);
foreach ($headers as $header) {
    header($header);
}

ob_clean();
echo $response->getBody();
