<?php

if (false) {
    $url = str_ireplace('.akrezing.ir', '', $_SERVER['SCRIPT_URI']);
} else {
    $url = 'https://www.mashreghnews.ir/news/1547891/%D8%AC%D8%B4%D9%86%DB%8C-%DA%A9%D9%87-%DA%AF%D9%86%D8%AF%D8%B4-%D8%AF%D8%B1%D8%A2%D9%85%D8%AF';
    // $url = 'https://melomusic.ir/wp-content/uploads/2022/09/aroosi.jpgg?asfasfasf=afsasf #dssd';
}

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;

require('../vendor/autoload.php');

function unparse_url(array $parsed): string
{
    $pass      = $parsed['pass'] ?? null;
    $user      = $parsed['user'] ?? null;
    $userinfo  = $pass !== null ? "$user:$pass" : $user;
    $port      = $parsed['port'] ?? 0;
    $scheme    = $parsed['scheme'] ?? "";
    $query     = $parsed['query'] ?? "";
    $fragment  = $parsed['fragment'] ?? "";
    $authority = (
        ($userinfo !== null ? "$userinfo@" : "") .
        ($parsed['host'] ?? "") .
        ($port ? ":$port" : "")
    );
    return (
        (\strlen($scheme) > 0 ? "$scheme:" : "") .
        (\strlen($authority) > 0 ? "//$authority" : "") .
        ($parsed['path'] ?? "") .
        (\strlen($query) > 0 ? "?$query" : "") .
        (\strlen($fragment) > 0 ? "#$fragment" : "")
    );
}

function srcHref($matches)
{
    $url = trim($matches[2]);

    $schemes = array('data:', 'magnet:', 'about:', 'javascript:', 'mailto:', 'tel:', 'ios-app:', 'android-app:');
    if (starts_with($url, $schemes)) {
        return $matches[0];
    }

    if (
        isset($matches[2])
        and $parsed = parse_url($matches[2])
        and isset($parsed['host'])
    ) {
        $parsed['scheme'] = 'http';
        $parsed['host'] = $parsed['host'] . '.akrezing.ir';
        $changed = unparse_url($parsed);
        return str_replace($url, $changed, $matches[0]);
    }

    return $matches[0];
}

$request = ServerRequest::fromGlobals()->withUri(new Uri($url));

$client = new Client([
    'curl' => [
        CURLOPT_CONNECTTIMEOUT     => 10,
        CURLOPT_TIMEOUT            => 0,
        // don't bother with ssl
        CURLOPT_SSL_VERIFYPEER    => false,
        CURLOPT_SSL_VERIFYHOST    => false,
        // we will take care of redirects
        CURLOPT_FOLLOWLOCATION    => false,
        CURLOPT_AUTOREFERER        => false
    ]
]);

try {
    $response = $client->send($request);
} catch (GuzzleHttp\Exception\ClientException $e) {
    $response = $e->getResponse();
}

$isHtml = (false !== strpos(implode(',', $response->getHeader('content-type')), 'text/html;'));

foreach ($response->getHeaders() as $headerKey => $headers) {
    header($headerKey . ':' . implode(',', $headers));
}

if (!$isHtml) {
    $bodyReader = $response->getBody();
    while (!$bodyReader->eof()) {
        echo $bodyReader->read(512);
        flush();
    }
} else {
    $body = $response->getBody()->getContents();
    $body = preg_replace_callback('@(?:src|href)\s*=\s*(["|\'])(.*?)\1@is', 'srcHref', $body);
    echo ($body);
    flush();
}
