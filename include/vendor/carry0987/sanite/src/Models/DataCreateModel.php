<?php
namespace carry0987\Sanite\Models;

use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Interfaces\DataCreateInterface;
use carry0987\Sanite\Utils\DBUtil;

abstract class DataCreateModel implements DataCreateInterface
{
    protected \PDO $connectdb;

    public function __construct(Sanite $sanite)
    {
        $this->connectdb = $sanite->getConnection();
    }

    /**
     *  Use transaction to create data
     *  @param array $queryArray
     *  @param array $dataArray
     *  @param bool $getAutoIncrement
     *  
     *  @return array|bool
     */
    public function createSingleData(array $queryArray, array $dataArray, bool $getAutoIncrement = false): array|bool
    {
        $result['execute'] = false;
        if (!isset($queryArray['query'])) return $result;
        $paramTypes = DBUtil::getPDOType($queryArray['bind'], $dataArray);
        //Get DB Create
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            foreach ($dataArray as $index => $value) {
                $stmt->bindValue($index + 1, $value, $paramTypes[$index]);
            }
            $result['execute'] = $stmt->execute();
            if ($result['execute']) {
                $result['auto_increment'] = (int) $this->connectdb->lastInsertId();
            }
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $getAutoIncrement ? $result : $result['execute'];
    }

    /**
     *  Use transaction to create multiple data
     *  @param array $queryArray
     *  @param array $dataArray
     *  
     *  @return bool
     */
    public function createMultipleData(array $queryArray, array $dataArray): bool
    {
        $result = false;
        //Get DB Create
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            $this->connectdb->beginTransaction();
            foreach ($dataArray as $value) {
                $types = DBUtil::getPDOType($queryArray['bind'], $value);
                foreach ($value as $k => $v) {
                    $stmt->bindValue($k + 1, $v, $types[$k]);
                }
                $stmt->execute();
            }
            $result = $this->connectdb->commit();
        } catch (\PDOException $e) {
            if ($this->connectdb->inTransaction()) {
                $this->connectdb->rollBack();
            }

            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result;
    }
}
