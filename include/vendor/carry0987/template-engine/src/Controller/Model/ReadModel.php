<?php
namespace carry0987\Template\Controller\Model;

use carry0987\Sanite\Models\DataReadModel;

class ReadModel extends DataReadModel
{
    public static $table = 'template';

    public function getVersion(string $tpl_path, string $tpl_name, string $tpl_type)
    {
        $queryArray = [
            'query' => 'SELECT tpl_hash, tpl_expire_time, tpl_verhash FROM '.self::$table.'
                    WHERE tpl_path = ? AND tpl_name = ? AND tpl_type = ?',
            'bind'  => 'sss'
        ];
        $dataArray = [$tpl_path, $tpl_name, $tpl_type];

        return $this->getSingleData($queryArray, $dataArray);
    }
}
