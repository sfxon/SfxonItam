<?php declare(strict_types=1);
 
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
 
abstract class EntityMapperAbstract extends QBMapper
{
    abstract public function countAll(?array $filters = null): int;
    abstract public function findAllPaged(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $limit = 20,
        int $offset = 0,
        ?array $filters = null,
        ?array $include = null ): array;
    abstract public function findById(int $id, ?array $include = null): array;

}