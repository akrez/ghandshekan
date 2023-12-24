<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;

require('../vendor/autoload.php');

class Xxx
{
    public $baseHost;
    //
    public $fakeUrl;
    public $fakeUrlParsed;
    //
    public $realUrl;
    public $realUrlParsed;


    public function __construct($baseHost, $fakeUrl)
    {
        $this->baseHost = $baseHost;
        //
        $this->fakeUrl = $fakeUrl;
        $this->fakeUrlParsed = static::parseUrl($fakeUrl);
        //
        $this->realUrl = static::fakeToReal($fakeUrl, $baseHost);
        $this->realUrlParsed = static::parseUrl($this->realUrl);
    }

    public static function parseUrl(string $url, int $component = -1)
    {
        return parse_url($url, $component);
    }

    public static function unparseUrl(array $parsed): string
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

        $scheme = '';
        if ($parsed['scheme']) {
            $scheme = $parsed['scheme'] . '://';
        }
        $userPass = '';
        if ($parsed['user']) {
            $userPass = $parsed['user'] . ($parsed['pass'] ? ':' . $parsed['pass'] : '') . '@';
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

    public static function realToFake($url, $baseHost, $fakeUrlParsed, $realUrlParsed)
    {
        $parsed = static::parseUrl($url);

        $scheme = (isset($parsed['scheme']) ? $parsed['scheme'] : $realUrlParsed['scheme']);
        $host = (isset($parsed['host']) ? $parsed['host'] : $realUrlParsed['host']);

        $parsed['scheme'] = $fakeUrlParsed['scheme'];
        $parsed['host'] = implode('.', [$scheme, $host, $baseHost]);

        return static::unparseUrl($parsed);
    }

    public static function fakeToReal($url, $baseHost)
    {
        $parsed = static::parseUrl($url);

        $realSchemeHost = str_replace('.' . $baseHost, '', $parsed['host']);

        [
            0 => $realScheme,
            1 => $realHost,
        ] = explode('.', $realSchemeHost, 2);

        $parsed['scheme'] = $realScheme;
        $parsed['host'] = $realHost;

        return static::unparseUrl($parsed);
    }

    public function convertBody($body)
    {
        $body = preg_replace_callback('@(?:src|href)\s*=\s*(["|\'])(.*?)\1@is', array($this, 'srcHref'), $body);
        $body = preg_replace_callback('@<form[^>]*action=(["\'])(.*?)\1[^>]*>@i', array($this, 'formAction'), $body);
        $body = preg_replace_callback('/content=(["\'])\d+\s*;\s*url=(.*?)\1/is', array($this, 'metaRefresh'), $body);
        $body = preg_replace_callback('@[^a-z]{1}url\s*\((?:\'|"|)(.*?)(?:\'|"|)\)@im', array($this, 'cssUrl'), $body);
        $body = preg_replace_callback('/@import (\'|")(.*?)\1/i', array($this, 'cssImport'), $body);
        $body = preg_replace_callback('/srcset=\"(.*?)\"/i', array($this, 'srcset'), $body);
        return $body;
    }

    private function srcset($matches)
    {
        $src = trim($matches[1]);
        $urls = preg_split('/\s*,\s*/', $src);
        foreach ($urls as $part) {
            $pos = strpos($part, ' ');
            if ($pos !== false) {
                $url = substr($part, 0, $pos);
                $changed = static::realToFake(
                    $url,
                    $this->baseHost,
                    $this->fakeUrlParsed,
                    $this->realUrlParsed
                );
                $src = str_replace($url, $changed, $src);
            }
        }
        return 'srcset="' . $src . '"';
    }

    private function cssImport($matches)
    {
        $url = trim($matches[2]);
        $changed = static::realToFake(
            $url,
            $this->baseHost,
            $this->fakeUrlParsed,
            $this->realUrlParsed
        );
        return str_replace($url, $changed, $matches[0]);
    }

    private function cssUrl($matches)
    {
        $url = trim($matches[1]);
        if (starts_with($url, 'data:')) {
            return $matches[0];
        }
        $changed = static::realToFake(
            $url,
            $this->baseHost,
            $this->fakeUrlParsed,
            $this->realUrlParsed
        );
        return str_replace($url, $changed, $matches[0]);
    }

    private function metaRefresh($matches)
    {
        $url = trim($matches[2]);
        $changed = static::realToFake(
            $url,
            $this->baseHost,
            $this->fakeUrlParsed,
            $this->realUrlParsed
        );
        return str_replace($url, $changed, $matches[0]);
    }

    private function formAction($matches)
    {
        $action = trim($matches[2]);
        if (!$action) {
            $action = $this->fakeUrl;
        }
        $changed = static::realToFake(
            $action,
            $this->baseHost,
            $this->fakeUrlParsed,
            $this->realUrlParsed
        );
        return str_replace($action, $changed, $matches[0]);
    }

    private function srcHref($matches)
    {
        $url = trim($matches[2]);
        $schemes = array('data:', 'magnet:', 'about:', 'javascript:', 'mailto:', 'tel:', 'ios-app:', 'android-app:');
        if (starts_with($url, $schemes)) {
            return $matches[0];
        }
        $changed = static::realToFake(
            $url,
            $this->baseHost,
            $this->fakeUrlParsed,
            $this->realUrlParsed
        );
        return str_replace($url, $changed, $matches[0]);
    }
}

$x = new Xxx('akrezing.ir', $_SERVER['SCRIPT_URI']);

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
