<?php
namespace carry0987\Sanite\Interfaces;

interface DataUpdateInterface
{
    public function updateSingleData(array $queryArray, array $dataArray): bool;
    public function updateMultipleData(array $queryArray, array $dataArray): bool;
}
