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

    public function isEntityValueInUse($entityFieldName, $id) {
        $allowedEntityIdFields = ['device_status_id', 'position_id', 'device_type_id', 'itam_user_id', 'merchant_id'];

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

    public function findById(int $id): Device {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
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
        $allowedColumns = [
            'assetNumber',
            'invoiceNumber',
            'name',
            'purchaseDate',
            'serialNumber',
            'serialNumber2',
        ];
        $col = in_array($orderBy, $allowedColumns, true) ? 'd.' . $orderBy : 'd.name';
        $col = strtolower(preg_replace('/[A-Z]/', '_$0', $col)); // CamelCase to SnakeCase umwandeln.
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

    public function countAll(?array $filters = null): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('*', 'count'));
        $qb->from($this->getTableName(), 'd');

        if ($filters !== null) {
            $this->applyFilters($qb, $filters);
        }

        return (int) $qb->executeQuery()->fetchOne();
    }

    private function applyFilters(IQueryBuilder $qb, array $filters): void {
        foreach ($filters as $key => $values) {
            if (empty($values)) {
                continue;
            }

            match ($key) {
                'name' => $this->applyLikeFilter($qb, 'd.name', $values),
                'deviceStatusId' => $this->applyInFilter($qb, 'd.device_status_id', $values),
                'positionId' => $this->applyInFilter($qb, 'd.position_id', $values),
                'deviceTypeId' => $this->applyInFilter($qb, 'd.device_type_id', $values),
                'itamUserId' => $this->applyInFilter($qb, 'd.itam_user_id', $values),
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
}