<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;

require('../vendor/autoload.php');

class Xxx
{
    public $realUrl;
    public $realHost;
    public $realScheme;

    public function __construct(
        public string $fakeUrl,
        public string $fakeHost,
        public string $fakeScheme = 'https'
    ) {
        $this->realUrl = $this->fakeToReal($fakeUrl);

        $parsed = $this->parseUrl($this->realUrl);
        $this->realHost = $parsed['host'];
        $this->realScheme = $parsed['scheme'];
    }

    public static function parseUrl(string $url, int $component = -1)
    {
        return parse_url($url, $component);
    }

    public static function unparseUrl(array $parsed, $host = null, $scheme = null): string
    {
        $parsed = $parsed + [
            'scheme' => null,
            'user' => null,
            'pass' => null,
            'host' => null,
            'port' => null,
            'path' => null,
            'query' => null,
            'fragment' => null,
        ];
        if (null !== $scheme) {
            $parsed['scheme'] = $scheme;
        }
        if (null !== $host) {
            $parsed['host'] = $host;
        }

        $scheme = '';
        if ($parsed['scheme']) {
            $scheme = $parsed['scheme'] . '://';
        }
        $userPass = '';
        if ($parsed['user']) {
            if ($parsed['pass']) {
                $userPass = $parsed['user'] . ':' . $parsed['pass'] . '@';
            } else {
                $userPass = $parsed['user'] . '@';
            }
        }
        $port = '';
        if ($parsed['port']) {
            $port = ':' . $parsed['port'];
        }
        $path = '';
        if ($parsed['path']) {
            $path = $parsed['path'];
        }
        $query = '';
        if ($parsed['query']) {
            $query = '?' . $parsed['query'];
        }
        $fragment = '';
        if ($parsed['fragment']) {
            $fragment = '#' . $parsed['fragment'];
        }

        return ($parsed['host'] ? $scheme . $userPass . $parsed['host'] . $port : '')  . $path . $query . $fragment;
    }

    public static function realToFake($realUrl, $fakeScheme, $fakeHost, $realScheme, $realHost)
    {
        $parsed = static::parseUrl($realUrl);

        $scheme = (isset($parsed['scheme']) ? $parsed['scheme'] : $realScheme);
        $host = (isset($parsed['host']) ? $parsed['host'] : $realHost);

        return static::unparseUrl(
            $parsed,
            implode('.', [$scheme, $host, $fakeHost]),
            $fakeScheme
        );
    }

    public function fakeToReal($fakeUrl)
    {
        $parsed = static::parseUrl($fakeUrl);

        $realSchemeHost = str_replace('.' . $this->fakeHost, '', $parsed['host']);

        [
            0 => $realScheme,
            1 => $realHost,
        ] = explode('.', $realSchemeHost, 2);

        return static::unparseUrl(
            $parsed,
            $realHost,
            $realScheme
        );
    }

    public function convertBody($body)
    {
        $body = preg_replace_callback('@(?:src|href)\s*=\s*(["|\'])(.*?)\1@is', [$this, 'srcHref'], $body);

        return $body;
    }

    public function srcHref($matches)
    {
        $url = trim($matches[2]);

        $schemes = array('data:', 'magnet:', 'about:', 'javascript:', 'mailto:', 'tel:', 'ios-app:', 'android-app:');
        if (starts_with($url, $schemes)) {
            return $matches[0];
        }

        $changed = $this->realToFake(
            $matches[2],
            $this->fakeScheme,
            $this->fakeHost,
            $this->realScheme,
            $this->realHost
        );

        return str_replace($url, $changed, $matches[0]);
    }
}

$x = new Xxx($_SERVER['SCRIPT_URI'], 'akrezing.ir', 'http');

// dd($x);

$request = ServerRequest::fromGlobals()->withUri(new Uri($x->realUrl));

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
    echo $x->convertBody($body);
}