<?php

declare(strict_types=1);

namespace MicroPHP\Data\Util;

class Str
{
    private static array $_strCache = [];

    public static function camel(string $str): string
    {
        if (isset(static::$_strCache['camel'][$str])) {
            return static::$_strCache['camel'][$str];
        }
        return static::$_strCache['camel'][$str] = lcfirst(static::studly($str));
    }

    public static function snake(string $str, string $delimiter = '_'): string
    {
        if (isset(static::$_strCache['snake'][$str])) {
            return static::$_strCache['snake'][$str];
        }
        if (! ctype_lower($str)) {
            $str = preg_replace('/\s+/u', '', ucwords($str));

            $str = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $str));
        }

        return static::$_strCache['snake'][$str] = $str;
    }

    public static function lower($value): null|array|bool|string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    public static function startsWith(string $haystack, string $prefix): bool
    {
        if (isset(static::$_strCache['startsWith'][$haystack][$prefix])) {
            return static::$_strCache['startsWith'][$haystack][$prefix];
        }

        return static::$_strCache['startsWith'][$haystack][$prefix] = str_starts_with($haystack, $prefix);
    }

    public static function studly(string $value, string $gap = ''): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return str_replace(' ', $gap, $value);
    }
}
