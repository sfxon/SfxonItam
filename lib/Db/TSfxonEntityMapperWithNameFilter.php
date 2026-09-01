<?php
declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;

trait TSfxonEntityMapperWithNameFilter {
    public function findByName(string $name): mixed
    {
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
}