<?php

function fw($file, $data)
{
    fwrite($file, $data);
    echo $data;
}

set_time_limit(600);
ignore_user_abort(true);

$request = file_get_contents('request2.txt');
$response = 'response.txt';

$socket = fsockopen("localhost", 80, $errno, $error);
if (!$socket) {
    die('Error: ' . $errno . '#' . $error);
}

$file = fopen($response, 'w');
if (!$file) {
    die('Error: file');
}

fwrite($socket, $request);

$firstLine = fgets($socket, 128);
if ($firstLine and false !== strpos($firstLine, 'HTTP/')) {
    fw($file, $firstLine);
} else {
    die('Error: firstLine');
}

$isResponseBody = false;
$contentLength = null;
$blockSize = 128;
$chunkLength = null;

while (!feof($socket)) {
    if ($isResponseBody) {
        if (null !== $contentLength and $contentLength > 0) {
            $data = fread($socket, $blockSize);
            fw($file, $data);
        } else {
            if ($chunkLength === null) {
                $data = trim(fgets($socket, 128));
                $chunkLength = hexdec($data);
            } else if ($chunkLength > 0) {
                $readLength = $chunkLength > $blockSize ? $blockSize : $chunkLength;
                $chunkLength -= $readLength;
                $data = fread($socket, $readLength);
                fw($file, $data);
                if ($chunkLength <= 0) {
                    fseek($socket, 2, SEEK_CUR);
                    $chunkLength = null;
                }
            } else {
                break;
            }
        }
    } else {
        $header = fgets($socket, 10240);
        fw($file, $header);
        $colonPos = strpos($header, ':');
        $headerName = strtolower(trim(substr($header, 0, $colonPos)));
        $headerValue = trim(substr($header, $colonPos + 1));
        if ($headerName == 'content-length') {
            $contentLength = (int) $headerValue;
        }
        if ($header == "\r\n") {
            $isResponseBody = true;
        }
    }
}

fclose($file);
fclose($socket);
