<?php
namespace carry0987\Template\Controller\Model;

use carry0987\Sanite\Models\DataCreateModel;

class CreateModel extends DataCreateModel
{
    public static $table = 'template';

    public function createVersion(string $tpl_path, string $tpl_name, string $tpl_type, string $tpl_hash, int $tpl_expire_time, string $tpl_verhash)
    {
        $queryArray = [
            'query' => 'INSERT INTO '.self::$table.' (tpl_path, tpl_name, tpl_type, tpl_hash, tpl_expire_time, tpl_verhash)
                    VALUES (?, ?, ?, ?, ?, ?)',
            'bind'  => 'ssssis'
        ];
        $dataArray = [$tpl_path, $tpl_name, $tpl_type, $tpl_hash, $tpl_expire_time, $tpl_verhash];

        return $this->createSingleData($queryArray, $dataArray);
    }
}
