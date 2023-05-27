<?php
require('vendor/autoload.php');

use Proxy\Http\Request;
use Proxy\Proxy;

$request = Request::createFromGlobals();

$proxy = new Proxy();

$proxy->getEventDispatcher()->addListener('request.before_send', function ($event) {
});

$proxy->getEventDispatcher()->addListener('request.sent', function ($event) {
});

$proxy->getEventDispatcher()->addListener('request.complete', function ($event) {
});

$response = $proxy->forward($request, "http://www.digikala.com");
$response->send();
