<?php
namespace carry0987\Sanite\Models;

use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Interfaces\DataDeleteInterface;
use carry0987\Sanite\Utils\DBUtil;

abstract class DataDeleteModel implements DataDeleteInterface
{
    protected \PDO $connectdb;

    public function __construct(Sanite $sanite)
    {
        $this->connectdb = $sanite->getConnection();
    }

    /**
     *  Delete single row of data
     *  @param array $queryArray
     *  @param array $dataArray
     *  
     *  @return bool
     */
    public function deleteSingleData(array $queryArray, array $dataArray): bool
    {
        $result = false;
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            $types = DBUtil::getPDOType($queryArray['bind'], $dataArray);
            foreach ($dataArray as $key => $val) {
                $stmt->bindValue($key + 1, $val, $types[$key]);
            }
            $result = $stmt->execute();
        } catch(\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     *  Use transaction to delete multiple data
     *  @param array $queryArray
     *  @param array $dataArray
     *  
     *  @return bool
     */
    public function deleteMultipleData(array $queryArray, array $dataArray): bool
    {
        $result = false;
        try {
            $this->connectdb->beginTransaction();
            $stmt = $this->connectdb->prepare($queryArray['query']);
            foreach ($dataArray as $value) {
                $types = DBUtil::getPDOType($queryArray['bind'], $value);
                foreach (array_keys($value) as $subKey) {
                    $stmt->bindValue($subKey + 1, $value[$subKey], $types[$subKey]);
                }
                $stmt->execute();
            }
            $result = $this->connectdb->commit();
        } catch(\PDOException $e) {
            if ($this->connectdb->inTransaction()) {
                $this->connectdb->rollBack();
            }

            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result;
    }
}
