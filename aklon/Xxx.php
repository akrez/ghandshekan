<?php

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
        $this->realUrl = static::decryptUrl($fakeUrl, $baseHost, $this->fakeUrlParsed);
        $this->realUrlParsed = static::parseUrl($this->realUrl);
    }

    public static function getCleanContentType($contentType)
    {
        return trim(preg_replace('@;.*@', '', $contentType));
    }

    public static function parseUrl(string $url, int $component = -1)
    {
        return parse_url($url, $component);
    }

    public static function strRotPass($str, $key, $isDecrypt = false)
    {
        $keyLength = strlen($key);
        $result = str_repeat(' ', strlen($str));
        for ($i = 0; $i < strlen($str); $i++) {
            if ($isDecrypt) {
                $ascii = ord($str[$i]) - ord($key[$i % $keyLength]);
            } else {
                $ascii = ord($str[$i]) + ord($key[$i % $keyLength]);
            }
            $result[$i] = chr($ascii);
        }
        return $result;
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

    public static function encryptUrl($url, $baseHost, $fakeUrlParsed = [], $realUrlParsed = [])
    {
        $parsed = static::parseUrl($url);

        $scheme = (isset($parsed['scheme']) ? $parsed['scheme'] : $realUrlParsed['scheme']);
        $host = (isset($parsed['host']) ? $parsed['host'] : $realUrlParsed['host']);

        $parsed['scheme'] = $fakeUrlParsed['scheme'];
        $parsed['host'] = implode('.', [$scheme, $host, $baseHost]);

        return static::unparseUrl($parsed);
    }

    public static function decryptUrl($url, $baseHost, $fakeUrlParsed = [])
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
                $changed = static::encryptUrl(
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
        $changed = static::encryptUrl(
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
        $changed = static::encryptUrl(
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
        $changed = static::encryptUrl(
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
        $changed = static::encryptUrl(
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
        $types = array('data:', 'magnet:', 'about:', 'javascript:', 'mailto:', 'tel:', 'ios-app:', 'android-app:');
        if (starts_with($url, $types)) {
            return $matches[0];
        }
        $changed = static::encryptUrl(
            $url,
            $this->baseHost,
            $this->fakeUrlParsed,
            $this->realUrlParsed
        );
        return str_replace($url, $changed, $matches[0]);
    }
}
