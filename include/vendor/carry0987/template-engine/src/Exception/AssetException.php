<?php
namespace carry0987\Template\Exception;

class AssetException extends \Exception
{
    public function __construct(string $message, string $file_msg)
    {
        $message = $file_msg !== null ? $file_msg.': '.$message : $message;
        $error = 'Asset Error: '.$message."\n";
        parent::__construct($error);
    }
}
