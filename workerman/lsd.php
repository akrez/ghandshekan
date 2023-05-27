<?php

use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

// #### http worker ####
$http_worker = new Worker('http://127.0.0.1:8080');

// 4 processes
$http_worker->count = 4;

// Emitted when data received
$http_worker->onMessage = function ($connection, $request) {
    echo "A\n";
    $connection->send("Hello World");
};

// Run all workers
Worker::runAll();
