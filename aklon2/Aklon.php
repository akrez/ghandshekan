<?php

class Aklon
{
    public static function getCleanContentType($contentType)
    {
        return trim(preg_replace('@;.*@', '', $contentType));
    }

    public static function encrypt($plaintext)
    {
        return base64_encode($plaintext);
    }

    public static function decrypt($data)
    {
        return base64_decode($data);
    }

    public static function isBase64Encode($data)
    {
        return !!(base64_encode(base64_decode($data, true)) === $data);
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

    public static function convertRelativeToAbsoluteUrl($absolute, $path)
    {
        $absoluteParsed = static::parseUrl($absolute);
        $relativeParsed = static::parseUrl($path);
        $absolutePath = '';

        if (isset($relativeParsed['path']) && isset($absoluteParsed['scheme']) && substr($relativeParsed['path'], 0, 2) === '//' && !isset($relativeParsed['scheme'])) {
            $path = $absoluteParsed['scheme'] . ':' . $path;
            $relativeParsed = static::parseUrl($path);
        }

        if (isset($relativeParsed['host'])) {
            return $path;
        }

        if (isset($absoluteParsed['scheme'])) {
            $absolutePath .= $absoluteParsed['scheme'] . '://';
        }

        if (isset($absoluteParsed['user'])) {
            if (isset($absoluteParsed['pass'])) {
                $absolutePath .= $absoluteParsed['user'] . ':' . $absoluteParsed['pass'] . '@';
            } else {
                $absolutePath .= $absoluteParsed['user'] . '@';
            }
        }

        if (isset($absoluteParsed['host'])) {
            $absolutePath .= $absoluteParsed['host'];
        }

        if (isset($absoluteParsed['port'])) {
            $absolutePath .= ':' . $absoluteParsed['port'];
        }

        if (isset($relativeParsed['path'])) {
            $pathSegments = explode('/', $relativeParsed['path']);

            if (isset($absoluteParsed['path'])) {
                $absoluteSegments = explode('/', $absoluteParsed['path']);
            } else {
                $absoluteSegments = array('', '');
            }

            $i = -1;
            while (++$i < count($pathSegments)) {
                $pathSegment  = $pathSegments[$i];
                $lastItem = end($absoluteSegments);

                switch ($pathSegment) {
                    case '.':
                        if ($i === 0 || empty($lastItem)) {
                            array_splice($absoluteSegments, -1);
                        }
                        break;
                    case '..':
                        if ($i === 0 && !empty($lastItem)) {
                            array_splice($absoluteSegments, -2);
                        } else {
                            array_splice($absoluteSegments, empty($lastItem) ? -2 : -1);
                        }
                        break;
                    case '':
                        if ($i === 0) {
                            $absoluteSegments = array();
                        } else {
                            $absoluteSegments[] = $pathSegment;
                        }
                        break;
                    default:
                        if ($i === 0 && !empty($lastItem)) {
                            array_splice($absoluteSegments, -1);
                        }

                        $absoluteSegments[] = $pathSegment;
                        break;
                }
            }

            $absolutePath .= '/' . ltrim(implode('/', $absoluteSegments), '/');
        }

        if (isset($relativeParsed['query'])) {
            $absolutePath .= '?' . $relativeParsed['query'];
        }

        if (isset($relativeParsed['fragment'])) {
            $absolutePath .= '#' . $relativeParsed['fragment'];
        }

        return $absolutePath;
    }

    public static function encryptUrl($current, $baseHost, $fakeUrl = '', $realUrl = '')
    {
        if ($realUrl) {
            $current = static::convertRelativeToAbsoluteUrl($realUrl, $current);
        }
        $parsedCurrent = static::parseUrl($current);
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

    public static function convertCss($body, $baseHost, $fakeUrl, $realUrl)
    {
        $body = preg_replace_callback('/@import (\'|")(.*?)\1/i', function ($matches) use ($baseHost, $fakeUrl, $realUrl) {
            $url = trim($matches[2]);
            $changed = static::encryptUrl($url, $baseHost, $fakeUrl, $realUrl);
            return str_replace($url, $changed, $matches[0]);
        }, $body);

        $body = preg_replace_callback('/\burl\(\s*+\K(?|(")((?>[^"\\\\]++|\\\\.)*+)"|(\')((?>[^\'\\\\]++|\\\\.)*+)\'|()([\S)]*+))(\s*+\))/ix', function ($matches) use ($baseHost, $fakeUrl, $realUrl) {
            $url = trim($matches[2]);
            $changed = static::encryptUrl($url, $baseHost, $fakeUrl, $realUrl);
            return str_replace($url, $changed, $matches[0]);
        }, $body);

        return $body;
    }

    public static function convertHtml($body, $baseHost, $fakeUrl, $realUrl)
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
