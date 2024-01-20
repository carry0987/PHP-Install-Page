<?php
require dirname(__FILE__).'/include/Core.php';

// Composer
use carry0987\Template as Template;

// Template setting
$options = array(
    'template_dir' => 'template/',
    'css_dir' => 'template/css/',
    'js_dir' => 'template/js/',
    'static_dir' => 'template/icon/',
    'cache_dir' => 'cache/',
    'auto_update' => true,
    'cache_lifetime' => 0
);

$template = new Template\Template;
$template->setOptions($options);

// Set template parameters
$date = date('Y-m-d');

include($template->loadTemplate('index.html'));
