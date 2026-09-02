<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Merchant>
 */
class MerchantMapper extends QBMapper {
    private string $tableNameAlias = 'me';

    use TSfxonEntityMapper;
    use TSfxonEntityMapperWithNameFilter;

    private const TABLE_NAME = 'sfxon_merchant';
    private const TABLE_ALIAS = 'me';

    private array $allowedEntityIdFields = [];

    private array $allowedSortColumns = [
        'name',
    ];

    private const JOIN_FILTERS = [];

    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE_NAME, DeviceStatus::class);
    }
}