<?php

class Aklon
{
    const KEY = '123234235235';
    const CIPHER = 'AES-128-CBC';

    public static function getCleanContentType($contentType)
    {
        return trim(preg_replace('@;.*@', '', $contentType));
    }

    public static function encrypt($plaintext)
    {
        return base64_encode($plaintext);
        $key = static::KEY;
        $ivlen = openssl_cipher_iv_length(static::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $raw = openssl_encrypt($plaintext, static::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $raw, $key, true);
        return base64_encode($iv . $hmac . $raw);
    }

    public static function decrypt($data)
    {
        return base64_decode($data);
        $key = static::KEY;
        $c = base64_decode($data);
        $ivlen = openssl_cipher_iv_length(static::CIPHER);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $raw = substr($c, $ivlen + $sha2len);
        $plaintext = openssl_decrypt($raw, static::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $raw, $key, true);
        if (hash_equals($hmac, $calcmac)) {
            return $plaintext;
        }
    }

    public static function isBase64Encode($data)
    {
        return !!(base64_encode(base64_decode($data, true)) === $data);
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

    public static function startsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && stripos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
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

    public static function encryptUrl($current, $baseHost, $fakeUrl = '', $realUrl = '')
    {
        $parsedCurrent = static::parseUrl($current);
        if ($realUrl) {
            $parsedRealUrl = static::parseUrl($realUrl);
            if (isset($parsedRealUrl['host']) and empty($parsedCurrent['host'])) {
                $parsedCurrent['host'] = $parsedRealUrl['host'];
            }
            if (isset($parsedRealUrl['scheme']) and empty($parsedCurrent['scheme'])) {
                $parsedCurrent['scheme'] = $parsedRealUrl['scheme'];
            }
        }
        return static::unparseUrl([
            'scheme' => 'http',
            'host' => $baseHost,
            'query' => 'q=' . static::encrypt(static::unparseUrl($parsedCurrent)),
        ]);
    }

    public static function decryptUrl($current, $baseHost, $fakeUrl = '')
    {
        $currentParsed = static::parseUrl($current);
        parse_str($currentParsed['query'], $urlQueries);
        $url = $urlQueries['q'];

        if (
            static::isBase64Encode($url) and
            $encryptedUrl = static::decrypt($url)
        ) {
            $url = $encryptedUrl;
        }

        return $url;
    }

    public static function convertBody($body, $baseHost, $fakeUrl, $realUrl)
    {
        $body = preg_replace_callback('@(?:src|href)\s*=\s*(["|\'])(.*?)\1@is', function ($matches) use ($baseHost, $fakeUrl, $realUrl) {
            $url = trim($matches[2]);
            $types = array('data:', 'magnet:', 'about:', 'javascript:', 'mailto:', 'tel:', 'ios-app:', 'android-app:');
            if (static::startsWith($url, $types)) {
                return $matches[0];
            }
            $changed = static::encryptUrl($url, $baseHost, $fakeUrl, $realUrl);
            return str_replace($url, $changed, $matches[0]);
        }, $body);

        $body = preg_replace_callback('@<form[^>]*action=(["\'])(.*?)\1[^>]*>@i', function ($matches) use ($baseHost, $fakeUrl, $realUrl) {
            $action = trim($matches[2]);
            if (!$action) {
                $action = $fakeUrl;
            }
            $changed = static::encryptUrl($action, $baseHost, $fakeUrl, $realUrl);
            return str_replace($action, $changed, $matches[0]);
        }, $body);

        $body = preg_replace_callback('/content=(["\'])\d+\s*;\s*url=(.*?)\1/is', function ($matches) use ($baseHost, $fakeUrl, $realUrl) {
            $url = trim($matches[2]);
            $changed = static::encryptUrl($url, $baseHost, $fakeUrl, $realUrl);
            return str_replace($url, $changed, $matches[0]);
        }, $body);

        $body = preg_replace_callback('@[^a-z]{1}url\s*\((?:\'|"|)(.*?)(?:\'|"|)\)@im', function ($matches) use ($baseHost, $fakeUrl, $realUrl) {
            $url = trim($matches[1]);
            if (static::startsWith($url, 'data:')) {
                return $matches[0];
            }
            $changed = static::encryptUrl($url, $baseHost, $fakeUrl, $realUrl);
            return str_replace($url, $changed, $matches[0]);
        }, $body);

        $body = preg_replace_callback('/@import (\'|")(.*?)\1/i', function ($matches) use ($baseHost, $fakeUrl, $realUrl) {
            $url = trim($matches[2]);
            $changed = static::encryptUrl($url, $baseHost, $fakeUrl, $realUrl);
            return str_replace($url, $changed, $matches[0]);
        }, $body);

        $body = preg_replace_callback('/srcset=\"(.*?)\"/i', function ($matches) use ($baseHost, $fakeUrl, $realUrl) {
            $src = trim($matches[1]);
            $urls = preg_split('/\s*,\s*/', $src);
            foreach ($urls as $part) {
                $pos = strpos($part, ' ');
                if ($pos !== false) {
                    $url = substr($part, 0, $pos);
                    $changed = static::encryptUrl($url, $baseHost, $fakeUrl, $realUrl);
                    $src = str_replace($url, $changed, $src);
                }
            }
            return 'srcset="' . $src . '"';
        }, $body);

        return $body;
    }
}
