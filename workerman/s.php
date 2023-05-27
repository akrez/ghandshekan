<?php

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http;

require_once __DIR__ . '/vendor/autoload.php';

$buffer = file_get_contents('request.txt');

/*
$httpConnection = new AsyncTcpConnection('https://shahab-tahrir.ir/');
$httpConnection->onConnect = function ($connection) {
    $connection->send('Hello');
};
$httpConnection->onMessage = function ($connection, $data) {
    echo "Recv: $data\n";
};
$httpConnection->onError = function ($connection, $code, $msg) {
    echo "Error: $msg\n";
};
$httpConnection->onClose = function ($connection) {
    echo "Connection closed\n";
};
$httpConnection->connect();
*/

// $httpConnection = new TcpConnection();
// $httpConnection->onMessage = function ($connection, $data) {
//     var_dump($data);
// };
// $httpConnection->send($buffer, true);


// $temp = tmpfile();
// $httpConnection = new TcpConnection($temp, 'https://shahab-tahrir.ir/');
// $httpConnection->onMessage = function ($httpConnection, $msg) {
//     echo "recv form stdin: $msg";
// };

// $httpConnection->send($buffer, true);


function send($buffer)
{
    // Parse http header.
    list($method, $addr, $http_version) = explode(' ', $buffer, 3);
    $url_data = parse_url($addr);
    $addr = !isset($url_data['port']) ? "{$url_data['host']}:80" : "{$url_data['host']}:{$url_data['port']}";

    $fs = @fsockopen(
        'aliakbarrezaei.ir',
        80,
        $errno,
        $errstr,
        60
    );

    fwrite($fs, $buffer);
    while (!feof($fs)) {
        echo fgets($fs);
    }
    fclose($fs);
}

$buffer = file_get_contents('request.txt');
send($buffer);
