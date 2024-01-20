<?php
if (defined('IN_INSTALL') === false) exit('Access Denied');
require dirname(__FILE__).'/inc.language.php';
require dirname(__FILE__).'/install_function.php';

function getInput($field, $default = '') {
    if (isset($_POST[$field])) {
        return input_filter($_POST[$field]);
    }
    if ($default !== '') {
        return $default;
    }
    global $LANG, $redirect_url;
    echo $LANG['common']['input_empty'];
    echo '<br /><a href="'.$redirect_url.'">'.$LANG['common']['back_page'].'</a>';
    exit();
}

// URL Seting
$url = ($SYSTEM['https'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$redirect_url = dirname(dirname($url));

// Get input from POST
$db_host = getInput('db_host');
$db_name = getInput('db_name');
$db_port = getInput('db_port');
$db_user = getInput('db_user');
$db_password = getInput('db_password');
$web_lang = getInput('lang', 'en_US');
$admin_username = getInput('admin_username');
$admin_password = getInput('admin_password');
$admin_psw_confirm = getInput('admin_psw_confirm');

// Default value
$default_language = 'en_US';
$web_timezone = 'UTC';
$get_time = time();
$get_path = rtrim(dirname(dirname(dirname($_SERVER['PHP_SELF']))), '/');
$get_url = $_SERVER['HTTP_HOST'].$get_path;

// Check password
if ($admin_password !== $admin_psw_confirm) {
    echo $LANG['common']['repassword_error'];
    echo '<a href="'.$redirect_url.'">'.$LANG['common']['back_page'].'</a>';
    exit();
}

// Check required fields
$requiredFields = [
    'db_host' => $db_host,
    'db_name' => $db_name,
    'db_port' => $db_port,
    'db_user' => $db_user,
    'db_password' => $db_password,
    'username' => $admin_username,
    'password' => $admin_password,
    'password_confirm' => $admin_psw_confirm,
];

foreach ($requiredFields as $fieldName => $value) {
    if ($value === '') {
        if ($fieldName === 'password_confirm') {
            $fieldName = 'password';
        }
        echo $LANG['common'][$fieldName.'_empty'];
        echo '<br /><a href="'.$redirect_url.'">'.$LANG['common']['back_page'].'</a>';
        exit();
    }
}

$set_admin_psw = password_hash($admin_password, PASSWORD_DEFAULT);
$data_check = true;
