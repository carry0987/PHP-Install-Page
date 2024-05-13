<?php
namespace Install;

use Install\Language;
use PDO;
use PDOException;

class Installer
{
    protected $lang;
    protected $data;
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
    const CONFIG_ROOT = __DIR__.'/../../';

    public function __construct(Language $lang, array $data, array $config = [])
    {
        $this->lang = $lang;
        $this->data = $data;
        $this->config = array_merge($this->getDefaultConfigValue(), $config);

        $this->dbHost = $this->getInput('db_host');
        $this->dbName = $this->getInput('db_name');
        $this->dbPort = $this->getInput('db_port');
        $this->dbUser = $this->getInput('db_user');
        $this->dbPassword = $this->getInput('db_password');
        $this->webLang = $this->getInput('lang', 'en_US');
        $this->adminUsername = $this->getInput('admin_username');
        $this->adminPassword = $this->getInput('admin_password');
        $this->adminPswConfirm = $this->getInput('admin_psw_confirm');
    }

    public function setUserQuery(string $query)
    {
        self::$userQuery = $query;
    }

    public function startInstall()
    {
        if ($this->validatePassword() !== true) {
            throw new \Exception($this->validatePassword());
        }
        if ($this->validateRequiredFields() !== true) {
            throw new \Exception($this->validateRequiredFields());
        }
        if ($this->checkInstallStatus() === true) {
            throw new \Exception($this->lang->getLang('install.installed'));
        }
        if ($this->checkPermission() !== true) {
            throw new \Exception($this->checkPermission());
        }
        $conn = $this->validateDatabaseConnection();
        if (is_string($conn)) {
            throw new \Exception($conn);
        }
        $createDatabase = $this->startCreateDatabase($conn);
        if ($createDatabase !== true) {
            throw new \Exception($createDatabase);
        }
        $createAdmin = $this->startCreateAdmin($conn);
        if ($createAdmin !== true) {
            throw new \Exception($createAdmin);
        }
        $generateConfigFile = $this->startGenerateConfigFile();
        if ($generateConfigFile !== true) {
            throw new \Exception($generateConfigFile);
        }

        return true;
    }

    public function checkInstalled()
    {
        if ($this->checkInstallStatus() === true) {
            return true;
        }

        return false;
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
        $msg = '<h1>Service unavailable</h1>'."\n";
        $msg .= '<br/>';
        $msg .= '<h2>Error Info :'.$e->getMessage().'</h2>';
        $msg .= '<h3>Error Code :'.$e->getCode().'</h3>'."\n";
        $msg .= '<h3>Error File :'.$e->getFile().'</h3>'."\n";
        $msg .= '<h3>Error Line :'.$e->getLine().'</h3>'."\n";

        throw new \Exception($msg);
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

    protected function getDefaultConfigValue()
    {
        return [
            'system_path' => '/',
            'config_path' => __DIR__ . '/../../config',
            'config_file' => 'config.inc.php',
            'lock_file' => 'installed.lock',
            'sql_file' => ['data', 'config'],
            'mroonga_file' => 'fulltext',
            'root_path' => __DIR__ . '/../../',
            'check_write_permission' => ['cache', 'config', 'template']
        ];
    }

    protected function checkInstallStatus()
    {
        $config_file = $this->config['config_path'].'/'.$this->config['config_file'];
        $lock_file = $this->config['config_path'].'/'.$this->config['lock_file'];
        if (file_exists($config_file) || file_exists($lock_file)) {
            return true;
        }

        return false;
    }

    protected function checkPermission()
    {
        $checkPermission = self::trimPath($this->config['root_path'].'/');
        $folders = $this->config['check_write_permission'];

        foreach ($folders as $folder) {
            if (!is_writeable($checkPermission.$folder)) {
                return self::showMsg('', $this->lang->getLang('install.permission_denied').'\''.$folder.'\'');
            }
        }

        return true;
    }

    protected function getInput(string $field, string $default = '')
    {
        if (isset($this->data[$field])) {
            return self::inputFilter($this->data[$field]);
        }
        if ($default !== '') {
            return $default;
        }

        throw new \Exception($this->lang->getLang('install.input_empty'));
    }

    protected function validatePassword()
    {
        if ($this->adminPassword !== $this->adminPswConfirm) {
            return $this->lang->getLang('install.repassword_error');
        }

        return true;
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
        $dbHost = $this->dbHost;
        $dbName = $this->dbName;
        $dbPort = $this->dbPort;
        try {
            $dsn = "mysql:host=$dbHost;dbname=$dbName;port=$dbPort;charset=utf8mb4";
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
        $sql_files = $this->config['sql_file'];
        try {
            foreach ($sql_files as $value) {
                if (file_exists(self::CONFIG_ROOT.'/database/'.$value.'.sql')) {
                    $sql = self::createDatabaseTable($conn, self::CONFIG_ROOT.'/database/'.$value.'.sql');
                    if ($sql !== true) {
                        return $sql;
                    }
                } else {
                    return self::showMsg($this->lang->getLang('install.file_not_exist'), $this->lang->getLang('install.file_not_found').': \''.'/database/'.$value.'.sql'.'\'');
                }
            }
            //Check Mroonga Support
            $mroonga_file = self::CONFIG_ROOT.'/database/'.$this->config['mroonga_file'].'.sql';
            if (self::checkMroongaSupport($conn) === true && file_exists($mroonga_file)) {
                $sql = self::createDatabaseTable($conn, $mroonga_file);
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
        $last_login = $join_date = time();
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

    protected function getConfigContent()
    {
        $templatePath = self::CONFIG_ROOT.'/config.default.php';
        if (!file_exists($templatePath)) {
            throw new \Exception("Default config template file not found.");
        }

        $systemPath = self::trimPath($this->config['system_path'].'/');
        if (rtrim($this->config['system_path'], '/') !== $systemPath) {
            $this->config['system_path'] = $systemPath;
        }

        $content = file_get_contents($templatePath);
        $replace = [
            '{DB_HOST}' => self::inputFilter($this->dbHost),
            '{DB_USER}' => self::inputFilter($this->dbUser),
            '{DB_PASSWORD}' => self::inputFilter($this->dbPassword),
            '{DB_NAME}' => self::inputFilter($this->dbName),
            '{DB_PORT}' => self::inputFilter($this->dbPort),
            '{SYSTEM_PATH}' => $this->config['system_path'],
            '{SESSION_ID}' => self::generateRandomString(),
            '{USER_KEY}' => self::generateRandomString(18),
            '{IMAGE_KEY}' => self::generateRandomString(18),
            '{MAINTENANCE_KEY}' => self::generateRandomString(18),
        ];

        foreach ($replace as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        return $content;
    }

    protected function startGenerateConfigFile()
    {
        $configContent = $this->getConfigContent();
        $result = self::generateFile($this->config['config_path'].'/'.$this->config['config_file'], $configContent);
        if ($result !== true) {
            throw new \Exception("Failed to generate the config file: {$result}");
        }
        $lockFileResult = self::generateFile($this->config['config_path'] . '/' . $this->config['lock_file'], '');
        if ($lockFileResult !== true) {
            throw new \Exception("Failed to create the lock file: {$lockFileResult}");
        }

        return true;
    }
}
