<?php
namespace carry0987\Template\Exception;

class TemplateException extends \Exception
{
    public function __construct(string $message, string $tplname)
    {
        $message = $tplname !== null ? $tplname.': '.$message : $message;
        $error = 'Template Error: '.$message."\n";
        parent::__construct($error);
    }
}
