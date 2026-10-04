<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\IDBConnection;

class DeviceTypeMapper extends EntityMapperAbstract
{
    use TSfxonEntityMapper;
    use TSfxonEntityMapperWithNameFilter;

    private const TABLE_NAME = 'sfxon_device_type';
    private const TABLE_ALIAS = 'dt';
    private array $allowedEntityIdFields = [
        'manufacturer_id'
    ];
    private array $allowedSortColumns = [
        'name',
    ];
    private const JOIN_FILTERS = [];

    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE_NAME, DeviceType::class);
    }
}