<?php
namespace carry0987\Template\Controller\Model;

use carry0987\Sanite\Models\DataUpdateModel;

class UpdateModel extends DataUpdateModel
{
    public static $table = 'template';

    public function updateVersion(string $tpl_path, string $tpl_name, string $tpl_type, string $tpl_hash, int $tpl_expire_time, string $tpl_verhash)
    {
        $queryArray = [
            'query' => 'UPDATE '.self::$table.'
                SET tpl_hash = ?, tpl_expire_time = ?, tpl_verhash = ?
                WHERE tpl_path = ? AND tpl_name = ? AND tpl_type = ?',
            'bind'  => 'sissss'
        ];
        $dataArray = [$tpl_hash, $tpl_expire_time, $tpl_verhash, $tpl_path, $tpl_name, $tpl_type];

        return $this->updateSingleData($queryArray, $dataArray);
    }
}
