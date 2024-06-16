<?php
namespace carry0987\Template\Controller;

use carry0987\Template\Exception\ControllerException;
use carry0987\Template\Controller\Model\ {
    CreateModel,
    ReadModel,
    UpdateModel
};
use carry0987\Sanite\Sanite;

class DBController
{
    private Sanite $sanite;
    private ReadModel $read;
    private CreateModel $create;
    private UpdateModel $update;

    public function __construct(array|\PDO|Sanite $dbConfig)
    {
        if ($dbConfig instanceof Sanite) {
            $this->sanite = $dbConfig;
        } else {
            $this->sanite = new Sanite($dbConfig);
        }
        $this->read = new ReadModel($this->sanite);
        $this->create = new CreateModel($this->sanite);
        $this->update = new UpdateModel($this->sanite);
    }

    public function isConnected(): bool
    {
        return !empty($this->sanite->getConnection());
    }

    public function setTableName(string $table): void
    {
        if ($this->read === null || $this->create === null || $this->update === null) {
            throw new ControllerException('Model is not set', 500);
        }

        $this->read::$table = $table;
        $this->create::$table = $table;
        $this->update::$table = $table;
    }

    public function getVersion(string $tpl_path, string $tpl_name, string $tpl_type): array|false
    {
        $version = $this->read->getVersion($tpl_path, $tpl_name, $tpl_type);

        return empty($version) ? false : $version;
    }

    public function updateVersion(string $tpl_path, string $tpl_name, string $tpl_type, string $tpl_hash, int $tpl_expire_time, string $tpl_verhash): bool
    {
        return $this->update->updateVersion($tpl_path, $tpl_name, $tpl_type, $tpl_hash, $tpl_expire_time, $tpl_verhash);
    }

    public function createVersion(string $tpl_path, string $tpl_name, string $tpl_type, string $tpl_hash, int $tpl_expire_time, string $tpl_verhash): bool
    {
        return $this->create->createVersion($tpl_path, $tpl_name, $tpl_type, $tpl_hash, $tpl_expire_time, $tpl_verhash);
    }
}
