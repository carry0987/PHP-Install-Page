<?php
namespace carry0987\Sanite\Exceptions;

class DatabaseException extends \Exception
{
    private $errorInfo;

    // Override constructor to pass error information
    public function __construct(string $message, mixed $code = 0, mixed $errorInfo = [])
    {
        parent::__construct($message, (int) $code);
        $this->errorInfo = $errorInfo;
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function getErrorInfo()
    {
        return $this->errorInfo;
    }
}
