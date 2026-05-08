<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<ItamUser>
 */
class ItamUserMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'sfxon_itam_user', ItamUser::class);
    }

    public function findById(int $id): ItamUser {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /** @return ItamUser[] */
    public function findAll(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->getTableName());
        return $this->findEntities($qb);
    }

    /** @return ItamUser[] */
    public function findAllPaged(
        string $orderBy = 'email',
        string $direction = 'ASC',
        int $limit = 20,
        int $offset = 0
    ): array {
        $allowedColumns = [ 'firstname', 'lastname', 'email' ];
        $col = in_array($orderBy, $allowedColumns, true) ? $orderBy : 'email';
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

    public function countAll(): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('*', 'count'));
        $qb->from($this->getTableName());
        $result = $qb->executeQuery();
        return (int) $result->fetchOne();
    }
}