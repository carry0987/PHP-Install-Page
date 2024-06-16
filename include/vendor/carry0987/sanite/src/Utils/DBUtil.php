<?php
namespace carry0987\Sanite\Utils;

use carry0987\Sanite\Exceptions\UtilsException;
use PDO;

class DBUtil
{
    public static function getPDOType(string $format_str, mixed &$values)
    {
        if (!is_array($values)) {
            if (strlen($format_str) !== 1) throw new UtilsException('Quantity Mismatch');
            $type = self::getParamType($format_str, $values);
            $values = array($values);
            return array($type);
        }
        $formats = str_split($format_str);
        if (count($formats) != count($values)) {
            throw new UtilsException('Quantity Mismatch for formats and values count');
        }
        $types = array();
        $values = array_values($values);
        foreach ($values as $index => $value) {
            $format = $formats[$index];
            $type = self::getParamType($format, $value);
            array_push($types, $type);
        }

        return $types;
    }

    public static function getParamType(string $param, mixed $value): int
    {
        switch ($param) {
            case 's':
                $type = is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR;
                break;
            case 'i':
                $type = is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_INT;
                break;
            default:
                throw new UtilsException('Unsupported format type');
        }

        return $type;
    }
}
