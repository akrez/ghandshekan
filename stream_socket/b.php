<?php

$serverSocket = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);
if ($serverSocket) {
    while ($conn = stream_socket_accept($serverSocket)) {
        // fwrite($conn, 'The local time is ' . date('n/j/Y g:i a') . "\n");
        // fclose($conn);
        /////
        $response ='HTTP/1.1 200 Ok
Date: Sun, 18 Oct 2012 10:36:20 GMT
Server: Apache/2.2.14 (Win32)
Content-Length: 230
Connection: Closed
Content-Type: text/html; charset=utf-8

<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
<title>200 200 Found</title>
</head>
<body>
<h1>Not Found</h1>
<p>The requested URL /t.html was not found on this server.</p>
</body>
</html>';
        fwrite($conn, $response);
        /////
    }
    fclose($conn);
    fclose($serverSocket);
}
