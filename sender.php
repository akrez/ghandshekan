<?php
$serverSocket = stream_socket_server("tcp://0.0.0.0:8080", $errno, $errstr);
if ($serverSocket) {
    while ($localClientConnection = stream_socket_accept($serverSocket)) {

        $request = "GET /a.mp4 HTTP/1.1
Accept: */*
Postman-Token: 121d7117-c884-4438-85d5-dfd8b6c6dde6
Host: localhost
Accept-Encoding: gzip, deflate, br
Connection: keep-alive

";
        $socket = fsockopen("localhost", 80, $errno, $error);
        if ($socket) {
            fwrite($socket, $request);
            while (!feof($socket)) {
                $line = fgetc($socket);
                fwrite($localClientConnection, $line);
            }
        }
        fclose($localClientConnection);
    }
    fclose($serverSocket);
}
