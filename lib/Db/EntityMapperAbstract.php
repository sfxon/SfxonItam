<?php declare(strict_types=1);
 
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
 
abstract class EntityMapperAbstract extends QBMapper
{
    /** @var array<string, array{type: string, sort: ?array}> column name => custom field sort info */
    protected array $customSortColumns = [];

    /**
     * @param array<string, array{type: string, sort: ?array}> $columns column name => custom field sort info
     */
    public function setCustomSortColumns(array $columns): void
    {
        $this->customSortColumns = $columns;
    }

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