<?php
require dirname(__FILE__).'/inc.core.php';
define('IN_INSTALL', true);
define('CONFIG_FILE', 'config.inc.php');
require dirname(__FILE__).'/install_function.php';
require dirname(__FILE__).'/inc.language.php';
require ROOT_PATH.'/../source/version.php';

//Set URL
$install_url = $_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['PHP_SELF']));
$base_url = ($SYSTEM['https']?'https':'http').'://'.$install_url;

//Check database install situation
if (file_exists(dirname(dirname(dirname(__FILE__))).'/config/'.CONFIG_FILE)) {
    echo $LANG['install']['installed'];
    echo '<br />';
    exit('<a href="../" style="color: blue;">Back</a>');
}

//Check directory permission
$check_permission = dirname(dirname(dirname(__FILE__)));
$folders = ['config', 'data'];

foreach ($folders as $folder) {
    if (!is_writeable($check_permission.'/'.$folder)) {
        echo "<h1>Permission Denied</h1><br /><h2>Please make \"$folder\" folder writeable</h2>";
        exit();
    }
}
