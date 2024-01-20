<?php
namespace Install;

use Install\Language;
use PDO;
use PDOException;

class Install
{
    protected $lang;
    protected $config;
    protected $dbHost;
    protected $dbName;
    protected $dbPort;
    protected $dbUser;
    protected $dbPassword;
    protected $webLang;
    protected $adminUsername;
    protected $adminPassword;
    protected $adminPswConfirm;
    protected static $userQuery = 'INSERT INTO user (username, password, group_id, language, last_login, join_date) VALUES (:username, :password, :group_id, :language, :last_login, :join_date)';

    const DIR_SEP = DIRECTORY_SEPARATOR;

    public function __construct(Language $lang, array $config)
    {
        $this->lang = $lang;
        $this->config = $config;
        // $this->redirectUrl = dirname(dirname($this->getCurrentUrl()));

        $this->dbHost = $this->getInput('db_host', '');
        $this->dbName = $this->getInput('db_name', '');
        $this->dbPort = $this->getInput('db_port', '');
        $this->dbUser = $this->getInput('db_user', '');
        $this->dbPassword = $this->getInput('db_password', '');
        $this->webLang = $this->getInput('lang', 'en_US');
        $this->adminUsername = $this->getInput('admin_username', '');
        $this->adminPassword = $this->getInput('admin_password', '');
        $this->adminPswConfirm = $this->getInput('admin_psw_confirm', '');
        $this->validatePassword();
        $this->validateRequiredFields();
    }

    public static function inputFilter(string $value)
    {
        $value = str_replace("'", "\"", "$value");
        $value = trim($value);
        $value = stripslashes($value);
        $value = htmlspecialchars($value);

        return $value;
    }

    public static function trimPath(string $path)
    {
        return str_replace(array('/', '\\', '//', '\\\\'), self::DIR_SEP, $path);
    }

    public static function getFileList(string $path = '/../../*')
    {
        return glob($path);
    }

    public static function showMsg(string $error_type, string $error_msg)
    {
        $error_info = '<h1>'.$error_type.'</h1>'."\n";
        $error_info .= '<h2>'.$error_msg.'</h2>'."\n";

        return $error_info;
    }

    public static function generateFile(string $file, $content)
    {
        $file = self::trimPath($file);
        try {
            if (file_exists($file) === true) {
                unlink($file);
            }
            file_put_contents($file, $content);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

    public static function createDatabaseTable(PDO $connectdb, string $filePath)
    {
        $templine = '';
        $lines = file($filePath);
        $error = '';
        // Loop through each line
        foreach ($lines as $line) {
            // Skip it if it's a comment
            if (substr($line, 0, 2) == '--' || $line == '') {
                continue;
            }
            // Add this line to the current segment
            $templine .= $line;
            // If it has a semicolon at the end, it's the end of the query
            if (substr(trim($line), -1, 1) == ';') {
                // Perform the query
                try {
                    $connectdb->exec($templine);
                } catch (PDOException $e) {
                    $error .= 'Error performing query "<b>'.$templine.'</b>": ' . $e->getMessage() . '<br /><br />';
                }
                // Reset temp variable to empty
                $templine = '';
            }
        }

        return (!empty($error)) ? $error : true;
    }

    public static function checkMroongaSupport(PDO $connectdb)
    {
        $result = false;
        try {
            $stmt = $connectdb->query('SHOW STORAGE ENGINES');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['Engine'] === 'Mroonga' && ($row['Support'] === 'YES' || $row['Support'] === 'DEFAULT')) {
                    $result = true;
                    break;
                }
            }
        } catch (PDOException $e) {
            self::showErrorMsg($e);
        }

        return $result;
    }

    public static function showErrorMsg($e)
    {
        echo '<h1>Service unavailable</h1>'."\n";
        echo '<br/>';
        echo '<h2>Error Info :'.$e->getMessage().'</h2>';
        echo '<h3>Error Code :'.$e->getCode().'</h3>'."\n";
        echo '<h3>Error File :'.$e->getFile().'</h3>'."\n";
        echo '<h3>Error Line :'.$e->getLine().'</h3>'."\n";
    }

    public static function generateRandomString(int $length = 16)
    {
        $word = 'abcdefghijkmnpqrstuvwxyz23456789';
        $len = strlen($word);
        $id = '';
        for ($i = 0; $i < $length; $i++) {
            $id .= $word[rand() % $len];
        }
        return $id;
    }

    protected function checkInstallStatus()
    {
        if (file_exists(dirname(dirname(dirname(__FILE__))).'/config/'.CONFIG_FILE)) {
            return true;
        }

        return false;
    }

    protected function checkPermission()
    {
        $checkPermission = dirname(dirname(dirname(__FILE__)));
        $folders = $this->config['check_write_permission'];

        foreach ($folders as $folder) {
            if (!is_writeable($checkPermission.'/'.$folder)) {
                return self::showMsg('', $this->lang->getLang('install.permission_denied').'\''.$folder.'\'');
            }
        }

        return true;
    }

    protected function getInput($field, $default)
    {
        if (isset($_POST[$field])) {
            return self::inputFilter($_POST[$field]);
        }
        if ($default !== '') {
            return $default;
        }

        throw new \Exception($this->lang->getLang('install.input_empty'));
    }

    protected function getDefaultConfig(array $config)
    {
        $db_host = $config['db_host'];
        $db_name = $config['db_name'];
        $db_port = $config['db_port'];
        $db_user = $config['db_user'];
        $db_password = $config['db_password'];
        $get_path = rtrim(dirname(dirname(dirname($_SERVER['PHP_SELF']))), '/');
        $session_id = self::generateRandomString();
        $user_uuid_key = self::generateRandomString(18);
        $image_uuid_key = self::generateRandomString(18);
        $maintenance_uuid_key = self::generateRandomString(18);

        $defaultConfig = <<<EOT
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

        return $defaultConfig;
    }

    protected function getCurrentUrl()
    {
    }

    protected function validatePassword()
    {
        return $this->adminPassword !== $this->adminPswConfirm;
    }

    protected function validateRequiredFields()
    {
        // Check required fields
        $requiredFields = [
            'db_host' => $this->dbHost,
            'db_name' => $this->dbName,
            'db_port' => $this->dbPort,
            'db_user' => $this->dbUser,
            'db_password' => $this->dbPassword,
            'username' => $this->adminUsername,
            'password' => $this->adminPassword,
            'password_confirm' => $this->adminPswConfirm,
            'lang' => $this->webLang
        ];

        foreach ($requiredFields as $fieldName => $value) {
            if ($value === '') {
                if ($fieldName === 'password_confirm') {
                    $fieldName = 'password';
                }
                $index = strpos($fieldName, 'db_') === 0 ? 'install' : 'database';
                return $this->lang->getLang($index.'.'.$fieldName.'_empty');
            }
        }

        return true;
    }

    protected function validateDatabaseConnection()
    {
        try {
            $dsn = "mysql:host=$this->dbHost;dbname=$this->dbName;port=$this->dbPort;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            $conn = new PDO($dsn, $this->dbUser, $this->dbPassword, $options);
        } catch (PDOException $e) {
            return $e->getMessage();
        }

        return $conn;
    }

    protected function startCreateDatabase(PDO $conn)
    {
        $sql_file = ['data', 'config', 'template', 'tag', 'inbox_mail', 'area_data', 'social'];
        try {
            foreach ($sql_file as $value) {
                if (file_exists(CONFIG_ROOT.'/database/'.$value.'.sql')) {
                    $sql = self::createDatabaseTable($conn, CONFIG_ROOT.'/database/'.$value.'.sql');
                    if ($sql !== true) {
                        return $sql;
                    }
                } else {
                    return self::showMsg($this->lang->getLang('install.file_not_exist'), $this->lang->getLang('install.file_not_found').': \''.'/database/'.$value.'.sql'.'\'');
                }
            }
            //Check Mroonga Support
            if (self::checkMroongaSupport($conn) === true && file_exists(CONFIG_ROOT.'/database/fulltext.sql')) {
                $sql = self::createDatabaseTable($conn, CONFIG_ROOT.'/database/fulltext.sql');
                if ($sql !== true) {
                    return $sql;
                }
            }
        } catch (PDOException $e) {
            return $e->getMessage();
        }

        return true;
    }

    protected function startCreateAdmin(PDO $conn)
    {
        $password = password_hash($this->adminPassword, PASSWORD_DEFAULT);
        $group_id = 1;
        $language = $this->webLang;
        $last_login = $join_date = date('Y-m-d H:i:s');
        try {
            $stmt = $conn->prepare(self::$userQuery);
            $stmt->bindValue(':username', $this->adminUsername, PDO::PARAM_STR);
            $stmt->bindValue(':password', $password, PDO::PARAM_STR);
            $stmt->bindValue(':group_id', $group_id, PDO::PARAM_INT);
            $stmt->bindValue(':language', $language, PDO::PARAM_STR);
            $stmt->bindValue(':last_login', $last_login, PDO::PARAM_INT);
            $stmt->bindValue(':join_date', $join_date, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            return $e->getMessage();
        }

        return true;
    }

    protected function startGenerateConfigFile()
    {
        $config_file = self::generateFile(CONFIG_ROOT.'/config/'.CONFIG_FILE, $this->getDefaultConfig($this->config));
        if ($config_file !== true) {
            return $config_file;
        }
        $generate_lock_file['1'] = self::generateFile(CONFIG_ROOT.'/config/installed.lock', '');
        foreach ($generate_lock_file as $generate_lock) {
            if ($generate_lock !== true) {
                return $generate_lock;
            }
        }

        return true;
    }
}
