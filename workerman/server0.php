<?php

set_time_limit(0);

function generateErrorMessage($message)
{
    return "HTTP/1.1 500 Internal Server Error\r\nContent-Length: " . strlen($message) . "\r\nContent-Type: text/html\r\n\r\n" . $message;
}

// Autoload.
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;

const LOCAL_SERVER_ADDRESS = "http://127.0.0.1:443";
// const REMOTE_SCRIPT_URL = "http://shahab-tahrir.ir/raw.php";
const REMOTE_SCRIPT_URL = "http://127.0.0.1/req/raw.php";

const HTTP_RAW_500 = "HTTP/1.1 500 Internal Server Error\r\nContent-Length: 0\r\nContent-Type: text/html\r\n\r\n";
const HTTP_RAW_200 = "HTTP/1.1 200 Connection Established\r\n\r\n";

$worker = new Worker(LOCAL_SERVER_ADDRESS);
$worker->onMessage = function (TcpConnection $connection, string $buffer) {
    list($method, $address, $httpVersion) = explode(' ', $buffer, 4);
    file_put_contents('asdasdasd.txt', $buffer);
    echo $address . "\r\n";
    if ($method === 'CONNECT') {
        $connection->send(HTTP_RAW_200, true);
    } else {
        try {
            if (true) {
                $connection->send(HTTP_RAW_200, true);
            } else {

                $curl = curl_init(REMOTE_SCRIPT_URL);
                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $buffer,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                ]);
                $response = curl_exec($curl);
                $responseString = ($response === false ? curl_error($curl) : $response);
                curl_close($curl);
                echo $method . '=>' . $buffer;
                $connection->send($responseString, true);
            }
        } catch (\Throwable $th) {
            $responseString = generateErrorMessage($th->getMessage());
            $connection->send($responseString, true);
        }
    }
};
Worker::runAll();
