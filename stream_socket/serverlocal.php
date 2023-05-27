<?php

error_reporting(-1);

$sslOptions = array(
    'local_cert' => __DIR__ . '/certificate.pem',
    'allow_self_signed' => true,
    'verify_peer' => false
);

$serverContext = stream_context_create(array("ssl" => $sslOptions));

$socket = stream_socket_server("ssl://127.0.0.1:4096", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $serverContext);

// Uncomment the following line to make the server asynchronous (and break everything):
//stream_set_blocking($socket, 0);

while ($conn = stream_socket_accept($socket)) {
    fwrite($conn, "Hello encrypted world!\n");
    fclose($conn);
}
