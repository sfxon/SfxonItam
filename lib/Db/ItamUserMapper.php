<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<ItamUser>
 */
class ItamUserMapper extends QBMapper {
    use TSfxonEntityMapper;

    private const TABLE_NAME = 'sfxon_itam_user';
    private const TABLE_ALIAS = 'iu';

    private array $allowedEntityIdFields = [];

    private array $allowedSortColumns = [
        'firstname', 'lastname', 'email',
    ];

    private const JOIN_FILTERS = [];

    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE_NAME, ItamUser::class);
    }

    // TODO: Have to check, what happens with case sensitivity.
    public function findByEmail(string $email): ?ItamUser {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq(
                    $qb->func()->lower('email'),
                    $qb->createNamedParameter(strtolower(trim($email)))
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