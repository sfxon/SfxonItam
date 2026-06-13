<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<CustomField>
 */
class CustomFieldMapper extends QBMapper {
    private string $tableNameAlias = 'cuf';

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'sfxon_custom_field_group', CustomField::class);
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

    public function findById(int $id): CustomField {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    public function findByName(string $name): ?CustomField {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq(
                    $qb->func()->lower('name'),
                    $qb->createNamedParameter(strtolower(trim($name)))
                )
            )
            ->setMaxResults(1);

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return null;
        }
    }

    /** @return CustomField[] */
    public function searchPaged(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $limit = 64,
        int $offset = 0,
        ?array $filters = null,
        ?array $include = null
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

        $mainData = $this->findEntities($qb);
        $relations = [];

        if (null !== $include && !empty($include)) {
            $relations = $this->loadRelations($mainData, $include);
        }

        // Extract the raw versions of the mainData entries (flat arrays, not longer objects).
        $mainDataFlat = [];

        foreach($mainData as $m) {
            $mainDataFlat[] = $m->jsonSerialize();
        }

        $result = [
            'mainData' => $mainDataFlat,
            'relations' => $relations,
        ];

        return $result;
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

    private function camelToSnake($input) {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $input));
    }

    private function loadRelations(array $mainData, array $include): array {
        $relations = [];
    
        foreach ($include as $relationName => $relationData) {
            $fields = ['id', 'name'];

            if(isset($relationData['fields'])) {
                $fields = $relationData['fields'];
            }

            // Expected result, for example: deviceStatusId
            $foreignKey = $relationName . 'Id';

            // Expected result, for example: getDeviceStatusId
            $getter = 'get' . ucfirst($foreignKey);

            // Expected result, for example: sfxon_device_status
            $table = 'sfxon_' . $this->camelToSnake($relationName);

            // Get all the ids of this foreign entity, that we want to get values for.
            $searchIdsIndexed = [];

            foreach($mainData as $mainDataEntry) {
                if(property_exists($mainDataEntry, $foreignKey)) {
                    $value = $mainDataEntry->{$getter}();
                    
                    if($value !== null) {
                        $searchIdsIndexed[$value] = true;
                    }
                }
            }

            $searchIds = array_keys($searchIdsIndexed);

            if (empty($searchIds)) {
                continue;
            }

            // Fetch all data for this entries.
            $qb = $this->db->getQueryBuilder();
            $qb->select($fields);
            $qb->from($table);
            $qb->where($qb->expr()->in(
                'id',
                $qb->createNamedParameter($searchIds, IQueryBuilder::PARAM_INT_ARRAY)
            ));

            $result = $qb->executeQuery();
            $indexedResult = [];

            while ($row = $result->fetch()) {
                $indexedResult[(int)$row['id']] = $row;
            }

            $result->closeCursor();

            $relations[$relationName] = $indexedResult;
        }

        return $relations;
    }
}