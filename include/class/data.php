<?php
require dirname(__FILE__).'/inc.core.php';
//Start install
define('IN_INSTALL', true);
require dirname(__FILE__).'/data.check.php';
require dirname(__FILE__).'/install_data_array.php';

//Set header
header('content-type:text/html;charset=utf-8');

//Get session id
$session_id = generateSessionID(16);
$image_uuid_key = generateSessionID(18);
$user_uuid_key = generateSessionID(18);
$maintenance_uuid_key = generateSessionID(18);
$config = <<<EOT
<?php
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASSWORD', '$db_password');
define('DB_NAME', '$db_name');
define('DB_PORT', '$db_port');
define('SYSTEM_PATH', '$get_path');
define('SESSION_ID', '$session_id');
define('USER_KEY', '$user_uuid_key');
define('IMAGE_KEY', '$image_uuid_key');
define('MAINTENANCE_KEY', '$maintenance_uuid_key');
define('DEBUG', false);

//Redis config
define('REDIS_HOST', '');
define('REDIS_PORT', 6379);
define('REDIS_DATABASE', 0);
define('REDIS_PASSWORD', '');
define('REDIS_MAXTTL', 86400);

EOT;

//Set root path
define('CONFIG_ROOT', dirname(dirname(dirname(__FILE__))));
define('CONFIG_FILE', 'config.inc.php');

//Check program install situation
if (file_exists(CONFIG_ROOT.'/install/installed.lock') === true || file_exists(CONFIG_ROOT.'/config/installed.lock') === true) {
    $data_check = false;
    echo '<h1>'.$LANG['install']['installed'].'</h1>';
    echo '<br/>';
    echo '<h2><a href="../" style="color: blue;">'.$LANG['common']['back_to'].$LANG['common']['home'].'</a></h2>';
    exit();
}

//Check database connection
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;port=$db_port;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $conn = new PDO($dsn, $db_user, $db_password, $options);
} catch (PDOException $e) {
    showErrorMsg($e);
    exit();
}

//Set Database
$sql_file = array('data','config','template','tag','inbox_mail','area_data','social');
if ($data_check === true) {
    try {
        foreach ($sql_file as $value) {
            if (file_exists(CONFIG_ROOT.'/install/data/'.$value.'.sql')) {
                $sql = createDatabaseTable($conn, CONFIG_ROOT.'/install/data/'.$value.'.sql');
                if ($sql !== true) exit($sql);
            } else {
                echo showMsg($LANG['common']['file_not_exist'], $LANG['common']['file_not_found'].': \''.'/install/data/'.$value.'.sql'.'\'');
                exit();
            }
        }
        //Check Mroonga Support
        if (checkMroongaSupport($conn) === true && file_exists(CONFIG_ROOT.'/install/data/fulltext.sql')) {
            $sql = createDatabaseTable($conn, CONFIG_ROOT.'/install/data/fulltext.sql');
            if ($sql !== true) exit($sql);
        }
    } catch (Exception $e) {
        showErrorMsg($e);
        exit();
    }
} else {
    echo '<h1>'.$LANG['common']['input_empty'].'</h1>'.'<br />';
    echo '<a href="../" style="color: blue;">Back</a>';
    exit();
}

//Data from /install/include/install_data_array.php
try {
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bindValue(':username', $admin_username, PDO::PARAM_STR);
    $user_stmt->bindValue(':password', $set_admin_psw, PDO::PARAM_STR);
    $user_stmt->bindValue(':group_id', $insert['group'], PDO::PARAM_INT);
    $user_stmt->bindValue(':language', $default_language, PDO::PARAM_STR);
    $user_stmt->bindValue(':last_login', $get_time, PDO::PARAM_INT);
    $user_stmt->bindValue(':join_date', $get_time, PDO::PARAM_INT);
    $user_stmt->execute();
} catch (PDOException $e) {
    $install_failed = true;
    showErrorMsg($e);
    echo '<h2>'.$LANG['common']['please'].' <a href="../../install" style="color: blue;">'.$LANG['common']['reinstall'].'</a> Program !</h2>';
    exit();
}

if (!isset($install_failed) || $install_failed !== true) {
    $config_file = generateFile(CONFIG_ROOT.'/config/'.CONFIG_FILE, $config);
    if ($config_file !== true) {
        echo $config_file;
        exit();
    }
    $generate_lock_file['1'] = generateFile(CONFIG_ROOT.'/config/installed.lock', '');
    foreach ($generate_lock_file as $generate_lock) {
        if ($generate_lock !== true) {
            echo $generate_lock;
            exit();
        }
    }
    echo '<h1>'.$LANG['install']['install_success'].'</h1>'."\n";
    echo '<h2>'.$LANG['install']['install_remove'].'</h2>'."\n";
    echo '<h3><a href="../../" style="color: blue;">Go !</a></h3>';
}
