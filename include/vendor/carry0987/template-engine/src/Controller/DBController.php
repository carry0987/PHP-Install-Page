<?php
namespace carry0987\Template\Controller;

use carry0987\Template\Exception\ControllerException;

class DBController
{
    private $db = null;
    private static $table = 'template';

    public function __construct(mixed $dbSettings)
    {
        if ($dbSettings instanceof \PDO) {
            $this->db = $dbSettings;
        }
        if ($this->db === null && is_array($dbSettings)) {
            try {
                $this->db = new \PDO('mysql:host='.$dbSettings['host'].';port='.$dbSettings['port'].';dbname='.$dbSettings['database'], $dbSettings['username'], $dbSettings['password']);
            } catch (\PDOException $e) {
                throw new ControllerException($e->getMessage(), $e->getCode());
            }
        }

        return $this;
    }

    public function isConnected()
    {
        return $this->db !== null;
    }

    public function setTableName(string $table)
    {
        self::$table = $table;

        return $this;
    }

    public function getVersion(string $tpl_path, string $tpl_name, string $tpl_type)
    {
        $tpl_query = 'SELECT tpl_md5, tpl_expire_time, tpl_verhash FROM '.self::$table.'
            WHERE tpl_path = :path AND tpl_name = :name AND tpl_type = :type';
        try {
            $tpl_stmt = $this->db->prepare($tpl_query);
            $tpl_stmt->execute([':path' => $tpl_path, ':name' => $tpl_name, ':type' => $tpl_type]);
            $tpl_row = $tpl_stmt->fetch(\PDO::FETCH_ASSOC);
            if ($tpl_row != false) {
                return $tpl_row;
            }
            return false;
        } catch (\PDOException $e) {
            throw new ControllerException($e->getMessage(), $e->getCode());
        }
    }

    public function createVersion(string $tpl_path, string $tpl_name, string $tpl_type, string $tpl_md5, int $tpl_expire_time, string $tpl_verhash)
    {
        $tpl_query = 'INSERT INTO '.self::$table.' (tpl_path, tpl_name, tpl_type, tpl_md5, tpl_expire_time, tpl_verhash)
            VALUES (:path, :name, :type, :md5, :expire_time, :verhash)';
        try {
            $tpl_stmt = $this->db->prepare($tpl_query);
            $tpl_stmt->execute([
                ':path' => $tpl_path,
                ':name' => $tpl_name,
                ':type' => $tpl_type,
                ':md5' => $tpl_md5,
                ':expire_time' => $tpl_expire_time,
                ':verhash' => $tpl_verhash
            ]);
        } catch (\PDOException $e) {
            throw new ControllerException($e->getMessage(), $e->getCode());
        }
    }

    public function updateVersion(string $tpl_path, string $tpl_name, string $tpl_type, string $tpl_md5, int $tpl_expire_time, string $tpl_verhash)
    {
        $tpl_query = 'UPDATE '.self::$table.'
            SET tpl_md5 = :md5, tpl_expire_time = :expire_time, tpl_verhash = :verhash
            WHERE tpl_path = :path AND tpl_name = :name AND tpl_type = :type';
        try {
            $tpl_stmt = $this->db->prepare($tpl_query);
            $tpl_stmt->execute([
                ':md5' => $tpl_md5,
                ':expire_time' => $tpl_expire_time,
                ':verhash' => $tpl_verhash,
                ':path' => $tpl_path,
                ':name' => $tpl_name,
                ':type' => $tpl_type
            ]);
        } catch (\PDOException $e) {
            throw new ControllerException($e->getMessage(), $e->getCode());
        }
    }
}
