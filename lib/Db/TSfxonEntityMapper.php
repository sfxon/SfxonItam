<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;

trait TSfxonEntityMapper
{
    /** @var string[] Track applied joins to avoid duplicates per query */
    private array $appliedJoinAliases = [];

    /** @var array<string, array>|null Static cache for filter field map */
    protected static ?array $filterFieldMap = null;

    private function applyFilters(IQueryBuilder $qb, array $filters): void
    {
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

    private function applyJoinFilter(IQueryBuilder $qb, array $config, mixed $values): void
    {
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

    /**
     * Default sort field, used when no orderBy is given or the given one is invalid.
     * Override in the concrete Mapper class if the entity has no 'name' field.
     */
    protected function getDefaultSortField(): string
    {
        return 'name';
    }

    private function getFilterFieldMap(): array
    {
        if (static::$filterFieldMap === null) {
            static::$filterFieldMap = [];
            foreach ($this->entityClass::getFieldDefinition() as $field) {
                $key = $field['propertyName'];
                static::$filterFieldMap[$key] = $field;
            }
        }
        return static::$filterFieldMap;
    }

    public function countAll(?array $filters = null): int
    {
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
        ?array $include = null ): array
    {
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->db->getQueryBuilder();
        $qb->select(self::TABLE_ALIAS . '.*')
            ->from($this->getTableName(), self::TABLE_ALIAS)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($filters !== null) {
            $this->applyFilters($qb, $filters);
        }

        if (!$this->applyRelationSort($qb, $orderBy, $dir)) {
            $fieldMap = $this->getFilterFieldMap();
            $fieldDef = $fieldMap[$orderBy] ?? null;
            $field = $fieldDef['name'] ?? $this->getDefaultSortField();
            $column = self::TABLE_ALIAS . '.' . $field;

            // Nextcloud natively uses binary tables, which means all sortings are case sensitive.
            // I think it is, because they need to sort filenames like this.
            // @TODO:
            // We do not want that, so we do a lower right now. It's not the nicest way and I should add some option later, maybe, to disable this,
            // because it can become inperformant.
            $filterType = $fieldDef['filterType'] ?? $this->resolveFilterType($fieldDef['type'] ?? '');
            $orderExpr = $filterType === 'like' ? $qb->func()->lower($column) : $column;

            $qb->orderBy($orderExpr, $dir)
                ->addOrderBy(self::TABLE_ALIAS . '.id', 'ASC');
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

    public function findById(int $id, ?array $include = null): array
    {
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

    /**
     * @return bool true if $orderBy was a relation sort and has been applied.
     */
    private function applyRelationSort(IQueryBuilder $qb, string $orderBy, string $direction): bool {
        // Whitelist lookup: user input is only ever used as an array key here.
        $relation = $this->getRelationSorts()[$orderBy] ?? null;
        if ($relation === null) {
            return false;
        }

        $entityClass = $relation['entity'];
        if (!is_subclass_of($entityClass, ISortableEntity::class)) {
            throw new \LogicException($entityClass . ' must implement ISortableEntity');
        }

        // Doctrine appends the direction unvalidated, so it must be normalised explicitly.
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $definition = $entityClass::getSortDefinition();
        // Use 'srt_' prefix avoids collisions with the aliases of JOIN_FILTERS (p, dt, ...).
        $alias = 'srt_' . preg_replace('/[^a-z0-9]/i', '', $orderBy);

        $this->assertSqlIdentifier($alias);
        $this->assertSqlIdentifier($relation['localKey']);
        $this->assertSqlIdentifier($definition['table']);

        // LEFT JOIN, so rows without a relation stay in the list.
        $qb->leftJoin(
            self::TABLE_ALIAS,
            $definition['table'],
            $alias,
            $qb->expr()->eq(self::TABLE_ALIAS . '.' . $relation['localKey'], $alias . '.id')
        );

        $joinAliases = [];
        foreach ($definition['joins'] ?? [] as $name => $join) {
            $joinAlias = $alias . '_' . $name;
            $this->assertSqlIdentifier($joinAlias);
            $this->assertSqlIdentifier($join['table']);
            $this->assertSqlIdentifier($join['localKey']);

            $qb->leftJoin(
                $alias,
                $join['table'],
                $joinAlias,
                $qb->expr()->eq($alias . '.' . $join['localKey'], $joinAlias . '.id')
            );
            $joinAliases[$name] = $joinAlias;
        }

        $first = true;

        foreach ($definition['columns'] as $column) {
            if (is_array($column)) {
                if (!isset($joinAliases[$column['join']])) {
                    throw new \LogicException('Unknown join in sort definition');
                }
                
                $tableAlias = $joinAliases[$column['join']];
                $columnName = $column['column'];
                $isText = $column['text'] ?? true;
            } else {
                $tableAlias = $alias;
                $columnName = $column;
                $isText = true;
            }
            $this->assertSqlIdentifier($columnName);

            $expression = $tableAlias . '.' . $columnName;
            if ($isText) {
                $expression = $qb->func()->lower($expression);
            }

            $first ? $qb->orderBy($expression, $direction) : $qb->addOrderBy($expression, $direction);
            $first = false;
        }

        $qb->addOrderBy(self::TABLE_ALIAS . '.id', 'ASC');

        return true;
    }

    private function assertSqlIdentifier(string $identifier): void {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new \LogicException('Invalid SQL identifier');
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

    /**
     * Sort key (as sent by the frontend) => ['entity' => class-string<ISortableEntity>, 'localKey' => fk column].
     * Override in the concrete Mapper.
     *
     * @return array<string, array{entity: class-string<ISortableEntity>, localKey: string}>
     */
    protected function getRelationSorts(): array {
        return [];
    }
}