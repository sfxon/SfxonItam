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
            // Joins – später bei Bedarf aktivieren
            // $qb->leftJoin('d', 'sfxonitam_device_types', 'dt', $qb->expr()->eq('d.device_type_id', 'dt.id'));
            // $qb->leftJoin('d', 'sfxonitam_positions', 'p', $qb->expr()->eq('d.position_id', 'p.id'));

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
                'serialNumber' => $this->applyLikeFilter($qb, 'd.serial_number', $values),
                default => null,
            };
        }
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

    private function applyInFilter(IQueryBuilder $qb, string $column, array $values): void
    {
        $params = array_map(fn($v) => $qb->createNamedParameter($v), $values);
        $qb->andWhere($qb->expr()->in($column, $params));
    }
}