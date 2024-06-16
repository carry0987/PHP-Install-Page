<?php
namespace carry0987\Sanite\Models;

use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Interfaces\DataReadInterface;
use carry0987\Sanite\Utils\DBUtil;

abstract class DataReadModel implements DataReadInterface
{
    protected \PDO $connectdb;

    public function __construct(Sanite $sanite)
    {
        $this->connectdb = $sanite->getConnection();
    }

    /**
     *  Get single row of data
     *  @param array $queryArray
     *  @param array|null $dataArray
     *  
     *  @return array
    */
    public function getSingleData(array $queryArray, array|null $dataArray = null): array
    {
        $result = [];
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            if (isset($dataArray)) {
                $types = DBUtil::getPDOType($queryArray['bind'], $dataArray);
                foreach ($dataArray as $index => $param) {
                    $stmt->bindValue($index + 1, $param, $types[$index]);
                }
            }
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result ? $result : [];
    }

    /**
     *  Get multiple row of data
     *  @param array $queryArray
     *  @param array|null $dataArray
     *  
     *  @return array
    */
    public function getMultipleData(array $queryArray, array|null $dataArray = null): array
    {
        $result = [];
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            if (isset($dataArray)) {
                $types = DBUtil::getPDOType($queryArray['bind'], $dataArray);
                foreach ($dataArray as $index => $param) {
                    $stmt->bindValue($index + 1, $param, $types[$index]);
                }
            }
            $stmt->execute();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     *  Get total row of data
     *  @param array $queryArray
     *  @param array|null $dataArray
     *
     *  @return int
    */
    public function getDataCount(array $queryArray, array|null $dataArray = null): int
    {
        $result = 0;
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            if (isset($dataArray)) {
                $types = DBUtil::getPDOType($queryArray['bind'], $dataArray);
                foreach ($dataArray as $index => $param) {
                    $stmt->bindValue($index + 1, $param, $types[$index]);
                }
            }
            $stmt->execute();
            $result = $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result;
    }
}
