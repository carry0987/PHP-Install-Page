<?php
namespace carry0987\Sanite\Interfaces;

interface DataCreateInterface
{
    public function createSingleData(array $queryArray, array $dataArray, bool $getAutoIncrement = false): array|bool;
    public function createMultipleData(array $queryArray, array $dataArray): bool;
}
