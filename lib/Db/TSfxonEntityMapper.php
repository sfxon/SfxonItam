<?php
declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;

trait TSfxonEntityMapper {
    /** @var string[] Track applied joins to avoid duplicates per query */
    private array $appliedJoinAliases = [];

    /** @var array<string, array>|null Static cache for filter field map */
    protected static ?array $filterFieldMap = null;

    private function applyFilters(IQueryBuilder $qb, array $filters): void {
        $this->appliedJoinAliases = [];
        $fieldMap = $this->getFilterFieldMap();

        foreach ($filters as $key => $values) {
            if (empty($values)) {
                continue;
            }

            // JOIN Filter
            if (isset(self::JOIN_FILTERS[$key])) {
                $this->applyJoinFilter($qb, self::JOIN_FILTERS[$key], $values);
                continue;
            }

            // Normal filter.
            if (!isset($fieldMap[$key])) {
                continue;
            }

            $field = $fieldMap[$key];
            $column = self::TABLE_ALIAS . '.' . $field['name'];

            $filterType = $field['filterType'] ?? $this->resolveFilterType($field['type']);

            match ($filterType) {
                'like' => $this->applyLikeFilter($qb, $column, $values),
                'in' => $this->applyInFilter($qb, $column, $values),
                'numericFromTo' => $this->applyNumericFromToFilter($qb, $column, $values),
                'dateFromTo' => $this->applyDateFromToFilter($qb, $column, $values),
                'none' => null,
                default => null,
            };
        }
    }

    private function applyJoinFilter(IQueryBuilder $qb, array $config, mixed $values): void {
        if (!in_array($config['alias'], $this->appliedJoinAliases, true)) {
            $qb->leftJoin(self::TABLE_ALIAS, $config['table'], $config['alias'], $config['condition']);
            $this->appliedJoinAliases[] = $config['alias'];
        }
        
        match ($config['handler']) {
            'in' => $this->applyInFilter($qb, $config['column'], $values),
            'like' => $this->applyLikeFilter($qb, $config['column'], $values),
            default => null,
        };
    }

    private function getFilterFieldMap(): array {
        if (static::$filterFieldMap === null) {
            static::$filterFieldMap = [];
            foreach ($this->entityClass::getFieldDefinition() as $field) {
                $key = $field['propertyName'];
                static::$filterFieldMap[$key] = $field;
            }
        }
        return static::$filterFieldMap;
    }

    public function countAll(?array $filters = null): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('*', 'count'));
        $qb->from($this->getTableName(), self::TABLE_ALIAS);

        if ($filters !== null) {
            $this->applyFilters($qb, $filters);
        }

        return (int) $qb->executeQuery()->fetchOne();
    }

    public function findAllPaged(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $limit = 20,
        int $offset = 0,
        ?array $filters = null,
        ?array $include = null
    ): array {
        $fieldMap = $this->getFilterFieldMap();
        
        if (isset($fieldMap[$orderBy])) {
            $col = self::TABLE_ALIAS . '.' . $fieldMap[$orderBy]['name'];
        } else {
            $col = self::TABLE_ALIAS . '.name';
        }

        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName(), self::TABLE_ALIAS)
            ->orderBy($col, $dir)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($filters !== null) {
            $this->applyFilters($qb, $filters);
        }

        $result = $this->findEntities($qb);
        $relations = [];

        if (null !== $include && !empty($include)) {
            $relations = $this->loadRelations($result, $include);
        }

        return [
            'mainData' => $result,
            'relations' => $relations,
        ];
    }

    public function findById(int $id, ?array $include = null): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
        $mainData = $this->findEntity($qb);

        $relations = [];

        if (null !== $include && !empty($include)) {
            $relations = $this->loadRelations([$mainData], $include);
        }

        return [
            'mainData' => $mainData,
            'relations' => $relations,
        ];
    }

    public function isEntityValueInUse(string $entityFieldName, int $id): bool {
        if (!in_array($entityFieldName, $this->allowedEntityIdFields, true)) {
            throw new \Exception('Entity field name \'' . $entityFieldName . '\' is not allowed.');
        }

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq($entityFieldName, $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);

        return $qb->executeQuery()->fetchAssociative() !== false;
    }

    private function applyDateFromToFilter(IQueryBuilder $qb, string $column, array $values): void {
        $from = null;
        $to = null;

        if (isset($values[0]) && $values[0] !== '' && $values[0] !== null) {
            $fromTimestamp = strtotime($values[0]);
            if ($fromTimestamp !== false) {
                $from = date('Y-m-d', $fromTimestamp);
            }
        }

        if (isset($values[1]) && $values[1] !== '' && $values[1] !== null) {
            $toTimestamp = strtotime($values[1]);
            if ($toTimestamp !== false) {
                $to = date('Y-m-d', $toTimestamp);
            }
        }

        if ($from === null && $to === null) {
            return;
        }

        if ($from !== null) {
            $qb->andWhere(
                $qb->expr()->gte($column, $qb->createNamedParameter($from)),
            );
        }

        if ($to !== null) {
            $qb->andWhere(
                $qb->expr()->lte($column, $qb->createNamedParameter($to)),
            );
        }
    }

    private function applyInFilter(IQueryBuilder $qb, string $column, array $values): void {
        $qb->andWhere($qb->expr()->in(
            $column,
            $qb->createNamedParameter(
                array_map('intval', $values),
                IQueryBuilder::PARAM_INT_ARRAY
            )
        ));
    }

    private function applyLikeFilter(IQueryBuilder $qb, string $column, array $values): void {
        $orX = $qb->expr()->orX();

        foreach ($values as $value) {
            $param = $qb->createNamedParameter('%' . $this->db->escapeLikeParameter(strtolower($value)) . '%');
            $orX->add($qb->expr()->like(
                $qb->func()->lower($column), 
                $param
            ));
        }

        $qb->andWhere($orX);
    }

    private function applyNumericFromToFilter(IQueryBuilder $qb, string $column, array $values): void {
        $from = null;
        $to = null;

        if (isset($values[0]) && $values[0] !== '' && $values[0] !== null) {
            $parsed = filter_var($values[0], FILTER_VALIDATE_FLOAT);
            if ($parsed !== false) {
                $from = $parsed;
            }
        }

        if (isset($values[1]) && $values[1] !== '' && $values[1] !== null) {
            $parsed = filter_var($values[1], FILTER_VALIDATE_FLOAT);
            if ($parsed !== false) {
                $to = $parsed;
            }
        }

        if ($from === null && $to === null) {
            return;
        }

        if ($from !== null) {
            $qb->andWhere(
                $qb->expr()->gte($column, $qb->createNamedParameter($from, IQueryBuilder::PARAM_STR))
            );
        }

        if ($to !== null) {
            $qb->andWhere(
                $qb->expr()->lte($column, $qb->createNamedParameter($to, IQueryBuilder::PARAM_STR))
            );
        }
    }

    private function camelToSnake(string $input): string {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $input));
    }

    private function loadNestedRelations(array $rows, array $with): array {
        foreach ($with as $nestedName => $nestedConfig) {
            $localKey = $nestedConfig['localKey'];
            $table = $nestedConfig['table'];
            $fields = $nestedConfig['fields'] ?? ['id', 'name'];

            $nestedIdsIndexed = [];

            foreach ($rows as $row) {
                if (isset($row[$localKey]) && $row[$localKey] !== null) {
                    $nestedIdsIndexed[$row[$localKey]] = true;
                }
            }

            $nestedIds = array_keys($nestedIdsIndexed);

            if (empty($nestedIds)) {
                continue;
            }

            $qb = $this->db->getQueryBuilder();
            $qb->select($fields)
                ->from($table)
                ->where($qb->expr()->in(
                    'id',
                    $qb->createNamedParameter($nestedIds, IQueryBuilder::PARAM_INT_ARRAY)
                ));

            $result = $qb->executeQuery();
            $nestedIndexed = [];

            while ($row = $result->fetch()) {
                $nestedIndexed[(int)$row['id']] = $row;
            }

            $result->closeCursor();

            foreach ($rows as $id => $row) {
                $nestedId = $row[$localKey] ?? null;
                $rows[$id][$nestedName] = $nestedId !== null && isset($nestedIndexed[$nestedId])
                    ? $nestedIndexed[$nestedId]
                    : null;
            }
        }

        return $rows;
    }

    private function loadRelations(array $data, array $include): array {
        $relations = [];
    
        foreach ($include as $relationName => $relationData) {
            $fields = $relationData['fields'] ?? ['id', 'name'];

            $foreignKey = $relationName . 'Id';
            $getter = 'get' . ucfirst($foreignKey);
            $table = 'sfxon_' . $this->camelToSnake($relationName);

            $searchIdsIndexed = [];

            foreach ($data as $d) {
                if (property_exists($d, $foreignKey)) {
                    $value = $d->{$getter}();
                    
                    if ($value !== null) {
                        $searchIdsIndexed[$value] = true;
                    }
                }
            }

            $searchIds = array_keys($searchIdsIndexed);

            if (empty($searchIds)) {
                continue;
            }

            $qb = $this->db->getQueryBuilder();
            $qb->select($fields)
                ->from($table)
                ->where($qb->expr()->in(
                    'id',
                    $qb->createNamedParameter($searchIds, IQueryBuilder::PARAM_INT_ARRAY)
                ));

            $result = $qb->executeQuery();
            $indexedResult = [];

            while ($row = $result->fetch()) {
                $indexedResult[(int)$row['id']] = $row;
            }

            $result->closeCursor();

            if (isset($relationData['with']) && !empty($indexedResult)) {
                $indexedResult = $this->loadNestedRelations($indexedResult, $relationData['with']);
            }

            $relations[$relationName] = $indexedResult;
        }

        return $relations;
    }

    private function resolveFilterType(string $dbType): string {
        return match ($dbType) {
            'VARCHAR', 'TEXT' => 'like',
            'BIGINT', 'INTEGER', 'INT' => 'in',
            'DECIMAL', 'FLOAT', 'DOUBLE' => 'numericFromTo',
            'DATE', 'DATETIME', 'TIMESTAMP' => 'dateFromTo',
            default => 'in',
        };
    }
}