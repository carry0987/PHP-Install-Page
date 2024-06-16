<?php
namespace carry0987\Utils;

use carry0987\Utils\Exceptions\UtilsException;
use DateTimeZone;
use DateTimeImmutable;

class Utils
{
    const DIR_SEP = DIRECTORY_SEPARATOR;
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    /**
     * Check if specified keys in an array are present and optionally not empty.
     *
     * @param array $array The array to check values in.
     * @param array $checkArray The keys to be checked within the array, set empty to check all keys.
     * @param bool $allowEmpty Flag to allow the values corresponding to keys in $checkArray to be empty.
     * 
     * @return bool Returns true if all keys exist (and are not empty, if $allowEmpty is false); otherwise, returns false.
     */
    public static function checkEmpty(array $array, array $checkArray = [], bool $allowEmpty = false): bool
    {
        // If $checkArray is empty, check all keys in $array
        if (empty($checkArray)) {
            $checkArray = array_keys($array);
        }

        foreach ($checkArray as $key) {
            // Check if the key is set in the input array
            if (isset($array[$key])) {
                // If not allowing empty values and the value is empty, set result to false
                if (!$allowEmpty && empty($array[$key])) {
                    return false;
                }
            } else {
                // If the key is not set, set result to false
                return false;
            }
        }

        return true;
    }

    /**
     * Generates an xxHash for the given string data.
     * 
     * @param string $data The string data to hash.
     * @param int $seed The seed value for the hash, if 0 default seed is used.
     * @param string $algorithm The hashing algorithm to use ('xxh32' or 'xxh64').
     * 
     * @return string The generated xxHash string.
     */
    public static function xxHash(string $data, int $seed = 0, string $algorithm = 'xxh64'): string
    {
        // Use seed option only if seed is not zero
        $options = $seed !== 0 ? ['seed' => $seed] : [];

        return hash($algorithm, $data, false, $options);
    }

    /**
     * Generates an xxHash for the given file.
     *
     * @param string $filePath The path to the file to hash.
     * @param int $seed The seed value for the hash, if 0 default seed is used.
     * @param string $algorithm The hashing algorithm to use ('xxh32' or 'xxh64').
     *
     * @return string|false The generated xxHash string, or false on failure.
     */
    public static function xxHashFile(string $filePath, int $seed = 0, string $algorithm = 'xxh64'): string|false
    {
        // Use seed option only if seed is not zero
        $options = $seed !== 0 ? ['seed' => $seed] : [];

        return hash_file($algorithm, $filePath, false, $options);
    }

    /**
     * Orders an array according to another array that specifies the order of keys.
     * 
     * @param array $array The array to order.
     * @param array $order An array of keys representing the desired order.
     * @param bool  $keepNotExists Whether to keep the keys that do not exist in the order array.
     * 
     * @return array Returns the ordered array.
     */
    public static function orderArray(array $array, array $order, bool $keetNotExists = false): array
    {
        $orderedArray = [];

        foreach ($order as $key) {
            if (array_key_exists($key, $array)) {
                $orderedArray[$key] = $array[$key];
            }
        }

        if ($keetNotExists) {
            $orderedArray = array_merge($orderedArray, $array);
        }

        return $orderedArray;
    }

    /**
     * Normalizes a file or directory path with uniform directory separators.
     * 
     * @param string $path The file or directory path to normalize.
     * 
     * @return string The normalized path with uniform directory separators.
     */
    public static function trimPath(string $path): string
    {
        return str_replace(array('/', '\\', '//', '\\\\'), self::DIR_SEP, $path);
    }

    /**
     * Converts a timestamp or string date into an ISO 8601 formatted date string.
     * 
     * @param int|string $value The timestamp or date string to convert.
     * @param string|null $timezone The timezone to be applied to the date-time string.
     * If null or not provided, the default system timezone will be used.
     *
     * @return string|null The formatted ISO 8601 date-time string or null in case of an error.
     */
    public static function getISODateTime(int|string $value, ?string $timezone = null): ?string
    {
        if ($timezone !== null && is_string($timezone)) {
            try {
                $timezone = new DateTimeZone($timezone);
            } catch (UtilsException $e) {
                return null;
            }
        } else {
            $timezone = new DateTimeZone(date_default_timezone_get());
        }

        try {
            $dateTime = is_int($value) ? (new DateTimeImmutable('@'.$value, $timezone)) : (new DateTimeImmutable($value, $timezone));
            // Convert to ISO 8601 format
            $result = $dateTime->format('c');
        } catch (UtilsException $e) {
            return null;
        }

        return $result;
    }

    /**
     * Converts a timestamp into a formatted date string.
     * 
     * @param int    $timestamp The timestamp to be converted.
     * @param string $format The formatting string to be applied to the date (default: 'Y/m/d/').
     * 
     * @return string The formatted date string according to the provided format.
     */
    public static function timestampToDate(int $timestamp, string $format = 'Y/m/d/'): string
    {
        $date = new DateTimeImmutable('@'.$timestamp);

        return $date->format($format);
    }

    /**
     * Generates a file or directory path with directories named after a given date.
     * 
     * If no timestamp is provided, the current time will be used.
     * 
     * @param int|null $timestamp The timestamp to generate the path from, defaults to current time if null.
     * 
     * @return string The generated path with directories structured as "Year/Month/Day/".
     */
    public static function getPathByDate(int $timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = time();   
        }
        $path = self::timestampToDate($timestamp, 'Y/m/d/');

        return self::trimPath($path);
    }

    /**
     * Check if any of the specified query parameters exists in the request.
     *
     * @param array $expectedParams The array of expected parameter keys.
     * @param array $queryParams The actual query parameters.
     *
     * @return bool Returns true if at least one of the expected parameters exists, false otherwise.
     */
    public static function hasAnyQueryParam(array $expectedParams, array $queryParams): bool
    {
        foreach ($expectedParams as $param) {
            if (isset($queryParams[$param])) {
                return true; // Return immediately if one parameter is found
            }
        }

        return false;
    }

    /**
     * Create a directory if it does not exist.
     *
     * @param string $path The path where the directory should be created.
     * @param int $permission The permission to be set for the created directory.
     * @return bool Returns true if the directory was created or already exists, false otherwise.
     */
    public static function makePath(string $path, int $permission = 0755): bool
    {
        // Check if directory already exists
        if (!file_exists($path)) {
            // Attempt to create the directory with the specified permissions
            return mkdir($path, $permission, true);
        } elseif (!is_dir($path)) {
            // If the path exists but is not a directory, return false
            return false;
        }

        // The directory already exists
        return true;
    }

    /**
     * Create a directory for the file path if it does not exist.
     *
     * @param string $filePath The full file path for which the directory should be created.
     * @param int $permission The permission to be set for the created directory.
     * @return bool Returns true if the directory was created or already exists, false otherwise.
     */
    public static function makeFilePath(string $filePath, int $permission = 0755): bool
    {
        // Get the directory part of the file path
        $dirPath = dirname($filePath);

        // Call makePath to create the directory if it does not exist
        return self::makePath($dirPath, $permission);
    }

    /**
     * Generate a random string with a specified length.
     * 
     * @param int $length The length of the random string to generate.
     * @param int $numeric Flag indicating whether the string should be numeric (1) or alphanumeric (0).
     * 
     * @return string A randomly generated string of the specified length.
     */
    public static function generateRandom(int $length, int $numeric = 0): string
    {
        $seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
        $hash = '';

        // Check if the string should be numeric
        if (!$numeric) {
            $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
            $length--;
        }

        // Set the maximum index for the seed string
        $max = strlen($seed) - 1;

        // Generate random string
        for ($i = 0; $i < $length; $i++) {
            $hash = $hash.$seed[mt_rand(0, $max)];
        }

        return $hash;
    }

    /**
     * Format a file size in bytes to a human-readable string.
     * 
     * @param int|float $size The file size in bytes.
     * 
     * @return string The formatted file size string.
     */
    public static function formatFileSize(int|float $size): string
    {
        $units = ['Byte', 'KB', 'MB'];
        $decimals = 2;
        $base = 1024;

        if ($size < $base) {
            $decimals = 0; // Bytes are not formatted to any decimal places.
            $unit = $units[0]; // Byte
            $formattedSize = $size;
        } elseif ($size < pow($base, 2)) {
            $unit = $units[1]; // KB
            $formattedSize = $size / $base;
        } else {
            $unit = $units[2]; // MB
            $formattedSize = $size / pow($base, 2);
        }

        return number_format($formattedSize, $decimals).' '.$unit;
    }

    /**
     * Filters an input to prevent XSS and remove unnecessary characters.
     * 
     * @param mixed $value The input value to be filtered.
     * 
     * @return string|null The filtered input as a string, or null if the input value is null.
     */
    public static function inputFilter(mixed $value): ?string
    {
        // Return null if the input value is null
        if ($value === null) return null;

        // Replace single quotes with double quotes and trim any whitespace from the beginning and end of the input
        $value = str_replace("'", "\"", $value);
        $value = trim($value);

        // Remove backslashes from the input (un-quotes a quoted string)
        $value = stripslashes($value);

        // Convert special characters to HTML entities to prevent XSS
        $value = htmlspecialchars($value);

        return $value;
    }

    /**
     * Sanitizes an associative array by applying inputFilter to each element or 
     * selectively only to elements that have keys matching those in $selectKey.
     * 
     * @param array $array The associative array to sanitize.
     * @param array|null $selectKey If not null, only elements with these keys will be sanitized.
     * 
     * @return array The sanitized associative array.
     */
    public static function arraySanitize(array $array, array $selectKey = null): array
    {
        $result = [];
        if (!empty($selectKey)) {
            $result = $array;
            // When $selectKey is not empty, sanitize only selected keys
            foreach ($selectKey as $key) {
                if (isset($array[$key])) {
                    // Sanitize and assign the value to the result array
                    $result[$key] = self::inputFilter($array[$key]);
                }
            }
        } else {
            // When $selectKey is empty, sanitize all keys in the array
            foreach ($array as $key => $value) {
                // Sanitize and reassign the value in the result array
                $result[$key] = self::inputFilter($value);
            }
        }

        return $result;
    }

    /**
     * Validates whether the given parameter is an integer or a string representing an integer.
     * 
     * @param string|int|null $num The variable to validate.
     * 
     * @return bool True if numeric or a numeric string, false otherwise.
     */
    public static function validateInteger(string|int $num = null): bool
    {
        // Return false if the $num is null
        if ($num === null) return false;

        // Check if $num is an integer or a string containing digits only
        return is_int($num) || ctype_digit(strval($num));
    }

    /**
     * Validates if the HTTP referer header matches the current server's host.
     * This can be useful for preventing CSRF attacks by ensuring that the request 
     * comes from the same origin.
     *
     * @return bool Returns true if the referer header is present and matches the host of the server; false otherwise.
     */
    public static function checkReferer(): bool
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            $refererhost = parse_url($_SERVER['HTTP_REFERER']);
            $refererhost['host'] .= (!empty($refererhost['port'])) ? (':'.$refererhost['port']) : '';
            if ($refererhost['host'] === $_SERVER['HTTP_HOST']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirects the browser to a specific URL using an HTTP response header.
     *
     * @param string $url The URL where the user should be redirected.
     * @param int $code The HTTP status code to use for the redirection (default is 303: See Other).
     *                  Commonly used HTTP codes for redirection are:
     *                  - 301 for permanent redirection
     *                  - 302 for temporary redirection (default is most browsers)
     *                  - 303 for redirection following a POST request
     *                  - 307 for temporary redirection without changing the request method
     *                  - 308 for permanent redirection without changing the request method
     */
    public static function redirectURL(string $url, int $code = 303): void
    {
        header('Location: '.$url, true, $code);
        exit();
    }

    /**
     * Set custom headers for the HTTP response
     * @param array|string $headers An associative array of headers to send or a string
     * @param bool $replace Whether to replace the existing headers
     * @param int $response_code Optional response code to send
     */
    public static function setHeader(array|string $headers, bool $replace = false, int $response_code = 0): void
    {
        // Check if headers is a string. If so, directly call header() function.
        if (is_string($headers)) {
            header($headers, $replace, $response_code);
            return;
        }

        // If headers is an array.
        foreach ($headers as $header => $value) {
            if (is_array($value)) {
                // If value is an array, it implies specific replace value and response code are passed.
                $headerValue = $value[0];
                $headerReplace = $value[1] ?? $replace; // Use specific replace value if provided, otherwise use the parameter's default.
                $headerResponseCode = $value[2] ?? $response_code; // Use specific response code if provided, otherwise use the parameter's default.
                header("$header: $headerValue", $headerReplace, $headerResponseCode);
                continue;
            }
            // If value is not an array, just a single header string.
            header("$header: $value", $replace, $response_code);
        }
    }

    /**
     * Concatenates URL with an associative array of query parameters.
     * 
     * Adds an array of params to the URL as a query string. If the URL already
     * contains queries, it appends using '&', otherwise with '?'. It URL-encodes
     * the keys and values to ensure it is a valid URL.
     *
     * @param string $url The base URL to which query parameters will be appended.
     * @param array $params An associative array of query parameters to append to the URL.
     *
     * @return string The concatenated URL complete with the passed query parameters.
     */
    public static function concateURL(string $url, array $params = []): string
    {
        // Early return if params array is empty; no changes needed to URL
        if (empty($params)) return $url;

        // Remove any trailing '&' or '?' from the base URL
        $url = rtrim($url, '&?');
        // Check if a '?' exists to determine if we are adding to an existing query string
        $url .= (strpos($url, '?') !== false) ? '&' : '?';
        // Initialize an array to hold the individual query parameter strings
        $paramArray = [];
        // Loop over each key-value pair in params array, URL-encode them and add them to the paramArray
        foreach ($params as $key => $value) {
            $paramArray[] = urlencode($key).'='.urlencode($value);
        }
        // Concatenate all parameters with '&' and append to the base URL
        $url .= implode('&', $paramArray);

        return $url;
    }

    /**
     * Sorts an array of associative arrays (or objects) by a specified key.
     * 
     * This method allows sorting of an array by values of a specific key in either
     * ascending or descending order. An Exception is thrown if the provided order
     * type is invalid or the key does not exist.
     * 
     * @param array $list The array of associative arrays to sort.
     * @param string $order The order direction ('ASC' for ascending, 'DESC' for descending).
     * @param string $key The key to sort by.
     * @return array The sorted array.
     * @throws UtilsException if the order type is not 'ASC' or 'DESC', or if the key does not exist.
     */
    public static function sortData(array $list, string $order, string $key = 'id'): array
    {
        $order = strtoupper($order);
        if ($order !== self::ORDER_ASC && $order !== self::ORDER_DESC) {
            throw new UtilsException('Invalid order type');
        }
        if (!isset($list[0][$key])) {
            throw new UtilsException('Invalid key');
        }

        // Sort multidimensional array by value
        uasort($list, function($a, $b) use ($key, $order) {
            if ($a[$key] === $b[$key]) {
                return 0;
            } else if ($order === self::ORDER_ASC) {
                return ($a[$key] < $b[$key]) ? -1 : 1;
            } else {
                return ($a[$key] > $b[$key]) ? -1 : 1;
            }
        });

        return $list;
    }

    /**
     * Converts a float number to an integer by a specified precision.
     * 
     * This method multiplies the float number by a power of 10 defined by the precision
     * parameter, and then casts the result to an integer. This is useful for handling
     * operations where float precision needs to be handled explicitly before processing.
     * 
     * @param mixed $floatNum The float number to convert.
     * @param int $precision The precision factor that determines the power of 10 to multiply with.
     * @return int The converted integer.
     */
    public static function toInteger(mixed $floatNum, int $precision = 2): int
    {
        $multiplier = pow(10, $precision);

        return intval($floatNum * $multiplier);
    }

    /**
     * Converts an integer to a float number by a specified precision.
     * 
     * This method divides the integer by a power of 10 defined by the precision
     * parameter to generate a float number. It is essentially the reverse operation
     * of toInteger, and is useful when working with fixed-point arithmetic.
     * 
     * @param int $intNum The integer number to convert.
     * @param int $precision The precision factor that determines the power of 10 to divide by.
     * @return float The converted float number.
     */
    public static function toFloat(int $intNum, int $precision = 2): float
    {
        $divisor = pow(10, $precision);

        return $intNum / $divisor;
    }
}
