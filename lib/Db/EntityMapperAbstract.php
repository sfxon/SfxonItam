<?php declare(strict_types=1);
 
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
 
abstract class EntityMapperAbstract extends QBMapper
{
    abstract public function findById(int $id, ?array $include = null): array;
}