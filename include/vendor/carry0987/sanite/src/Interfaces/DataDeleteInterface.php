<?php
namespace carry0987\Sanite\Interfaces;

interface DataDeleteInterface
{
    public function DeleteSingleData(array $queryArray, array $dataArray): bool;
    public function DeleteMultipleData(array $queryArray, array $dataArray): bool;
}
