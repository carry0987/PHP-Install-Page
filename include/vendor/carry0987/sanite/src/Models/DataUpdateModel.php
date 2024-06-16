<?php
namespace carry0987\Sanite\Models;

use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Interfaces\DataUpdateInterface;
use carry0987\Sanite\Utils\DBUtil;

abstract class DataUpdateModel implements DataUpdateInterface
{
    protected \PDO $connectdb;

    public function __construct(Sanite $sanite)
    {
        $this->connectdb = $sanite->getConnection();
    }

    /**
     *  Update single row of data
     *  @param array $queryArray
     *  @param array $dataArray
     *  
     *  @return bool
     */
    public function updateSingleData(array $queryArray, array $dataArray): bool
    {
        $result = false;
        // Get DB Update
        if (!isset($queryArray['query'])) return $result;
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            $types = DBUtil::getPDOType($queryArray['bind'], $dataArray);
            foreach ($dataArray as $i => $value) {
                $stmt->bindValue($i + 1, $value, $types[$i]);
            }
            $result = $stmt->execute();
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     *  Use transaction to update multiple data
     *  @param array $queryArray
     *  @param array $dataArray
     *  
     *  @return bool
     */
    public function updateMultipleData(array $queryArray, array $dataArray): bool
    {
        $result = false;
        //Get DB Update
        if (!isset($queryArray['query'])) return $result;
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            $this->connectdb->beginTransaction();
            foreach ($dataArray as $value) {
                $bind_params = DBUtil::getPDOType($queryArray['bind'], $value);
                foreach ($bind_params as $i => $type) {
                    $stmt->bindValue($i + 1, $value[$i], $type);
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
