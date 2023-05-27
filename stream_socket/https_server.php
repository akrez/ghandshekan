<?php

$local_cert = __DIR__ . "/server.pem";
$pem_passphrase = 'abracadabra'; // empty for no passphrase

$local_cert = __DIR__.'/certificate.pem';

$context = stream_context_create();

// local_cert must be in PEM format
stream_context_set_option($context, 'ssl', 'local_cert', $local_cert);
// stream_context_set_option($context, 'ssl', 'passphrase', $pem_passphrase);
stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
stream_context_set_option($context, 'ssl', 'verify_peer', false);
stream_context_set_option($context, 'ssl', 'verify_peer_name', false);

// Create the server socket
$server = stream_socket_server('ssl://127.0.0.1:443', $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
stream_socket_enable_crypto($server, false);


while (true) {
    $buffer = '';
    $client = stream_socket_accept($server);
    var_dump($client);
    if ($client) {
        // Read until double CRLF
        while (!preg_match('/\r?\n\r?\n/', $buffer))
            $buffer .= fread($client, 2046);
        // Respond to client
        fwrite($client,  "200 OK HTTP/1.1\r\n"
            . "Connection: close\r\n"
            . "Content-Type: text/html\r\n"
            . "\r\n"
            . "Hello World! " . microtime(true)
            . "\n<pre>{$buffer}</pre>");
        fclose($client);
    }
}
