<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Device>
 */
class DeviceMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'sfxon_device', Device::class);
    }

    public function countAll(?array $filters = null): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('*', 'count'));
        $qb->from($this->getTableName(), 'd');

        if ($filters !== null) {
            $this->applyFilters($qb, $filters);
        }

        return (int) $qb->executeQuery()->fetchOne();
    }

    public function isEntityValueInUse($entityFieldName, $id) {
        $allowedEntityIdFields = [
            'device_status_id',
            'device_type_id',
            'itam_user_id',
            'merchant_id',
            'position_id',
            'quantity_unit_id'
        ];

        if(!in_array($entityFieldName, $allowedEntityIdFields)) {
            throw new \Exception('Entity field name \'' . $entityFieldName . '\' is not allowed.');
        }

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq($entityFieldName, $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);

        $result = $qb->executeQuery()->fetchAssociative();

        if ($result === false) {
            return false;
        }

        return true;
    }

    public function findById(int $id, ?array $include = null ): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
        $mainData = $this->findEntity($qb);

        $relations = [];

        if (null !== $include && !empty($include)) {
            $relations = $this->loadRelations([$mainData], $include);
        }

        $result = [
            'mainData' => $mainData,
            'relations' => $relations,
        ];

        return $result;
    }

    // TODO: Have to check, what happens with case sensitivity.
    public function findByName(string $name): ?Device
    {
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

    /** @return Device[] */
    public function findAll(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->getTableName());
        return $this->findEntities($qb);
    }

    /** @return Device[] */
    public function findAllPaged(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $limit = 20,
        int $offset = 0,
        ?array $filters = null
    ): array {
        $allowedSortColumns = [
            'assetNumber',
            'invoiceNumber',
            'name',
            'purchaseDate',
            'quantity',
            'serialNumber',
            'serialNumber2',
        ];
        $col = in_array($orderBy, $allowedSortColumns, true) ? 'd.' . $orderBy : 'd.name';
        $col = $this->camelToSnake($col);
 
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName(), 'd')
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
                'name' => $this->applyLikeFilter($qb, 'd.name', $values),
                'quantity' => $this->applyNumericFromToFilter($qb, 'd.quantity', $values),
                'quantityUnitId' => $this->applyInFilter($qb, 'd.quantity_unit_id', $values),
                'deviceStatusId' => $this->applyInFilter($qb, 'd.device_status_id', $values),
                'positionId' => $this->applyInFilter($qb, 'd.position_id', $values),
                'deviceTypeId' => $this->applyInFilter($qb, 'd.device_type_id', $values),
                'itamUserId' => $this->applyInFilter($qb, 'd.itam_user_id', $values),
                'locationId' => $this->applyInFilterViaTable($qb, 'd.position_id', 'sfxon_position', 'p.location_id', $values),
                'manufacturerId' => $this->applyInFilterViaTable($qb, 'd.device_type_id', 'sfxon_device_type', 'p.manufacturer_id', $values),
                'serialNumber' => $this->applyLikeFilter($qb, 'd.serial_number', $values),
                'serialNumber2' => $this->applyLikeFilter($qb, 'd.serial_number2', $values),
                'assetNumber' => $this->applyLikeFilter($qb, 'd.asset_number', $values),
                'merchantId' => $this->applyInFilter($qb, 'd.merchant_id', $values),
                'invoiceNumber' => $this->applyLikeFilter($qb, 'd.invoice_number', $values),
                'purchaseDate' => $this->applyDateFromToFilter($qb, 'd.purchase_date', $values),
                default => null,
            };
        }
    }

    private function applyDateFromToFilter(IQueryBuilder $qb, string $column, array $values): void {
        // Check if both dates where submitted.
        $from = null;
        $fromTimestamp = null;
        $to = null;
        $toTimestamp = null;

        if(count($values) > 0 && isset($values[0])) {
            $fromTimestamp = strtotime($values[0]);

            if($fromTimestamp === false) {
                $fromTimestamp = null;
            } else {
                $from = date('Y-m-d', $fromTimestamp);
            }
        }

        if(count($values) > 1 && isset($values[1])) {
            $toTimestamp = strtotime($values[1]);
            $to = date('Y-m-d', $toTimestamp);
        }

        if($from === null && $to === null) {
            return;
        }

        if($from !== null) {
            $qb->andWhere(
                $qb->expr()->gte($column, $qb->createNamedParameter($from)),
            );
        }

        if($to !== null) {
            $qb->andWhere(
                $qb->expr()->lte($column, $qb->createNamedParameter($to)),
            );
        }
    }

    private function applyInFilter(IQueryBuilder $qb, string $column, array $values): void {
        $qb->andWhere($qb->expr()->in(
            $column,
            $qb->createNamedParameter(
                array_map('intval', $values),  // ← String zu Integer
                IQueryBuilder::PARAM_INT_ARRAY
            )
        ));
    }

    private function applyInFilterViaTable(
        IQueryBuilder $qb,
        string $localColumn, // e.g. 'd.position_id'
        string $lookupTable, // e.g. 'sfxon_position'
        string $lookupColumn, // e.g. 'p.location_id'
        array $values
    ): void {
        if (empty($values)) {
            return;
        }

        $subQb = $this->db->getQueryBuilder();
        $subQb->select('id')
            ->from($lookupTable, 'p')
            ->where($subQb->expr()->in(
                $lookupColumn,
                $subQb->createNamedParameter(
                    array_map('intval', $values),
                    IQueryBuilder::PARAM_INT_ARRAY
                )
            ));

        $ids = array_column(
            $subQb->executeQuery()->fetchAllAssociative(),
            'id'
        );

        if (empty($ids)) {
            $qb->andWhere('1 = 0');
            return;
        }

        $qb->andWhere($qb->expr()->in(
            $localColumn,
            $qb->createNamedParameter(
                array_map('intval', $ids),
                IQueryBuilder::PARAM_INT_ARRAY
            )
        ));
    }

    private function applyLikeFilter(IQueryBuilder $qb, string $column, array $values): void {
        // Bei mehreren Werten: OR-Verknüpfung
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

    private function applyNumericFromToFilter(IQueryBuilder $qb, string $column, array $values): void {
        $from = null;
        $to = null;

        if (isset($values[0]) && $values[0] !== '') {
            $parsed = filter_var($values[0], FILTER_VALIDATE_FLOAT);
            if ($parsed !== false) {
                $from = $parsed;
            }
        }

        if (isset($values[1]) && $values[1] !== '') {
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

    private function camelToSnake($input) {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $input));
    }

    private function loadRelations(array $devices, array $include): array {
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

            foreach($devices as $device) {
                if(property_exists($device, $foreignKey)) {
                    $value = $device->{$getter}();
                    
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