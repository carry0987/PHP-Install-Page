<?php
namespace carry0987\Template\Exception;

class ControllerException extends \Exception
{
    public function __construct(string $message, mixed $code)
    {
        $error = '<h1>Service unavailable</h1>'."\n";
        $error .= '<h2>Error Info :'.$message.'</h2>'."\n";
        $error .= '<h3>Error Code :'.$code.'</h3>'."\n";
        parent::__construct($error);
    }
}
