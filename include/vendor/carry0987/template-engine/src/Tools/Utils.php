<?php
namespace carry0987\Template\Tools;

use carry0987\Template\Template;

class Utils
{
    public static function dashPath(string $path)
    {
        $path = ltrim($path, '/\\');
        $path = rtrim($path, '/\\');

        return str_replace(array('/', '\\', '//', '\\\\'), '::', $path);
    }

    public static function trimPath(string $path)
    {
        return str_replace(array('/', '\\', '//', '\\\\'), Template::DIR_SEP, $path);
    }

    public static function trimRelativePath(string $path)
    {
        $hash = substr_count($path, '../');
        $hash = ($hash !== 0) ? substr(md5($hash), 0, 6).'/' : '';
        $path = str_replace('../', '', $path);

        return $hash.$path;
    }

    public static function makePath(string $path)
    {
        $dirs = explode(Template::DIR_SEP, dirname(self::trimPath($path)));
        if (!is_writeable($dirs[0])) {
            return false;
        }
        $tmp = '';
        foreach ($dirs as $dir) {
            $tmp = $tmp.$dir.Template::DIR_SEP;
            if (!file_exists($tmp) && !mkdir($tmp, 0755, true)) {
                return $tmp;
            }
        }

        return true;
    }

    /**
     * Generates cryptographically secure random strings
     * 
     * @param int $length The length of the generated string
     * @param bool $numeric Whether to generate a numeric
     * 
     * @return string The generated random string
     */
    public static function generateRandom(int $length, bool $numeric = false)
    {
        if ($numeric === true) {
            $bytes = random_bytes($length);
            // Convert to decimal - each byte is in the range 0-255 which is perfect for the mt_rand() function
            $rand = '';
            for ($i = 0; $i < $length; $i++) {
                $rand .= mt_rand(0, 9);
            }
            return $rand;
        } else {
            $bytes = random_bytes($length);
            // Convert to hexadecimal and take the desired length
            return substr(bin2hex($bytes), 0, $length);
        }
    }
}
