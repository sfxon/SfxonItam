<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Position>
 */
class PositionMapper extends QBMapper
{
    use TSfxonEntityMapper;
    use TSfxonEntityMapperWithNameFilter;

    private const TABLE_NAME = 'sfxon_position';
    private const TABLE_ALIAS = 'po';
    private array $allowedEntityIdFields = ['location_id'];
    private array $allowedSortColumns = [
        'name',
    ];
    private const JOIN_FILTERS = [
        'locationId' => [
            'table' => 'sfxon_position',
            'alias' => 'p',
            'condition' => 'd.position_id = p.id',
            'column' => 'p.location_id',
            'handler' => 'in',
        ],
    ];

    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE_NAME, Position::class);
    }

    public function findByNameAndLocationId(string $name, ?int $locationId): ?Position
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq(
                    $qb->func()->lower('name'),
                    $qb->createNamedParameter(strtolower(trim($name)))
                )
            );

        if ($locationId === null) {
            $qb->andWhere($qb->expr()->isNull('location_id'));
        } else {
            $qb->andWhere(
                $qb->expr()->eq('location_id', $qb->createNamedParameter($locationId, IQueryBuilder::PARAM_INT))
            );
        }

        $qb->setMaxResults(1);

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return null;
        }
    }

    public function isEntityValueInUse($entityFieldName, $id) {
        $allowedEntityIdFields = ['location_id'];

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
}