<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<DeviceStatus>
 */
class DeviceStatusMapper extends QBMapper {
    private string $tableNameAlias = 'ds';

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'sfxon_device_status', DeviceStatus::class);
    }

    public function countAll(?array $filters = null): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('*', 'count'));
        $qb->from($this->getTableName(), $this->tableNameAlias);

        if ($filters !== null) {
            $this->applyFilters($qb, $filters);
        }

        return (int) $qb->executeQuery()->fetchOne();
    }

    public function findById(int $id): DeviceStatus {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    // TODO: Have to check, what happens with case sensitivity.
    public function findByName(string $name): ?DeviceStatus {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('name', $qb->createNamedParameter($name)))
            ->setMaxResults(1);

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return null;
        }
    }

    #[\Deprecated(message: "Will be removed.", since: "1.9")]
    /** @return DeviceStatus[] */
    public function findAll(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->getTableName());
        return $this->findEntities($qb);
    }

    #[\Deprecated(message: "Will be replaced by searchPaged", since: "1.9")]
    /** @return DeviceStatus[] */
    public function findAllPaged(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $limit = 20,
        int $offset = 0
    ): array {
        $allowedColumns = [ 'name', ];
        $col = in_array($orderBy, $allowedColumns, true) ? $orderBy : 'name';
        $col = strtolower(preg_replace('/[A-Z]/', '_$0', $col)); // CamelCase to SnakeCase umwandeln.
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->orderBy($col, $dir)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    /** @return DeviceStatus[] */
    public function searchPaged(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $limit = 20,
        int $offset = 0,
        ?array $filters = null
    ): array {
        $allowedColumns = [ 'name', ];
        $col = in_array($orderBy, $allowedColumns, true) ? $orderBy : 'name';
        $col = strtolower(preg_replace('/[A-Z]/', '_$0', $col)); // Convert camel case to snake case.
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName(), $this->tableNameAlias)
            ->orderBy($col, $dir)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if($filters !== null) {
            $this->applyFilters($qb, $filters);
        }

        return $this->findEntities($qb);
    }

    private function applyFilters(IQueryBuilder $qb, array $filters): void {
        foreach ($filters as $key => $values) {
            if (empty($values)) {
                continue;
            }

            match ($key) {
                'name' => $this->applyLikeFilter($qb, $this->tableNameAlias . '.name', $values),
                default => null,
            };
        }
    }

    private function applyLikeFilter(IQueryBuilder $qb, string $column, array $values): void {
        // For multiple values: OR combination
        $orX = $qb->expr()->orX();

        foreach ($values as $value) {
            $param = $qb->createNamedParameter('%' . $this->db->escapeLikeParameter(strtolower($value)) . '%');
            $orX->add($qb->expr()->like(
                $qb->func()->lower($column), 
                $param)
            );
        }

        $qb->andWhere($orX);
    }
}