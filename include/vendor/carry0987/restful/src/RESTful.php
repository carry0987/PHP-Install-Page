<?php
/**
 *  A simple RESTful web services class
 *  You can extend the requirements based on this class
*/
namespace carry0987\RESTful;

class RESTful
{
    private static $httpVersion = 'HTTP/1.1';
    private static $allowedHttpMethods = ['GET', 'POST'];
    const HTTP_STATUS_CODES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    public static function setAllowedHttpMethod(array $allowedHttpMethods)
    {
        self::$allowedHttpMethods = $allowedHttpMethods;
    }

    public static function getAllowedHttpMethod(string $method = null)
    {
        return $method ? in_array($method, self::$allowedHttpMethods) : self::$allowedHttpMethods;
    }

    public static function verifyHttpMethod(bool $getMethod = false)
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        if (!in_array($requestMethod, self::$allowedHttpMethods)) {
            self::setHttpHeader('application/json', 405);
            header('Allow:'.implode(',', self::$allowedHttpMethods));
            exit();
        }

        return $getMethod ? $requestMethod : true;
    }

    public static function setHttpVersion(string $httpVersion)
    {
        self::$httpVersion = $httpVersion;
    }

    public static function setHttpHeader(string $contentType, int $statusCode)
    {
        if (empty($contentType)) {
            $contentType = 'application/json';
        }

        if (!headers_sent()) {
            $statusMessage = self::getHttpStatusMessage($statusCode);
            header(self::$httpVersion.' '.$statusCode.' '.$statusMessage);
            header('Content-Type:'.$contentType);
        } else {
            error_log('Tried to set headers after they were already sent.');
        }
    }

    public static function getHttpStatusMessage(int $statusCode)
    {
        return self::HTTP_STATUS_CODES[$statusCode] ?? self::HTTP_STATUS_CODES[500];
    }

    public static function encodeResponse(mixed $rawData)
    {
        $requestContentType = $_SERVER['HTTP_ACCEPT'];

        if (strpos($requestContentType, 'application/json') !== false) {
            return self::encodeJSON($rawData);
        } elseif (strpos($requestContentType, 'text/html') !== false) {
            return self::encodeHTML($rawData);
        } elseif (strpos($requestContentType, 'application/xml') !== false) {
            return self::encodeXML($rawData);
        }

        return self::encodeJSON($rawData); // Default to JSON if no acceptable header is found.
    }

    public static function encodeHTML(array $responseData)
    {
        $htmlResponse = '<table border=\'1\'>';
        foreach ($responseData as $key => $value) {
            $htmlResponse .= '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
        }
        $htmlResponse .= '</table>';

        return $htmlResponse;
    }

    public static function encodeJSON(mixed $responseData)
    {
        $jsonResponse = json_encode($responseData);

        return $jsonResponse;
    }

    public static function encodeXML(array $responseData)
    {
        // Create SimpleXMLElement object
        $xml = new \SimpleXMLElement('<?xml version="1.0"?><site></site>');
        foreach ($responseData as $key => $value) {
            $xml->addChild($key, $value);
        }

        return $xml->asXML();
    }
}
