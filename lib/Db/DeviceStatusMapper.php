<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<DeviceStatus>
 */
class DeviceStatusMapper extends QBMapper {
    use TSfxonEntityMapper;
    use TSfxonEntityMapperWithNameFilter;

    private const TABLE_NAME = 'sfxon_device_status';
    private const TABLE_ALIAS = 'ds';

    private array $allowedEntityIdFields = [];

    private array $allowedSortColumns = [
        'name',
    ];

    private const JOIN_FILTERS = [];

    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE_NAME, DeviceStatus::class);
    }
}