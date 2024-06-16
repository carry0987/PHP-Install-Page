<?php
namespace carry0987\Template\Tools;

use carry0987\Utils\Utils as UtilsBase;

class Utils extends UtilsBase
{
    public static function dashPath(string $path)
    {
        $path = ltrim($path, '/\\');
        $path = rtrim($path, '/\\');

        return str_replace(array('/', '\\', '//', '\\\\'), '::', $path);
    }

    public static function trimRelativePath(string $path)
    {
        $hash = substr_count($path, '../');
        $hash = ($hash !== 0) ? substr(self::xxHash($hash), 0, 6).'/' : '';
        $path = str_replace('../', '', $path);

        return $hash.$path;
    }

    public static function sliceString(string|array $value): array|string
    {
        if (is_array($value)) return (count($value) === 1) ? array_pop($value) : $value;

        $filtered = array_filter(explode(',', $value), function($str) {
            return !empty($str);
        });

        return (count($filtered) === 1) ? array_pop($filtered) : array_values($filtered);
    }
}
