<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Device>
 */
class DeviceMapper extends QBMapper {
    use TSfxonEntityMapper;
    use TSfxonEntityMapperWithNameFilter;

    private const TABLE_NAME = 'sfxon_device';
    private const TABLE_ALIAS = 'd';

    private array $allowedEntityIdFields = [
        'device_status_id',
        'device_type_id',
        'itam_user_id',
        'merchant_id',
        'position_id',
        'quantity_unit_id'
    ];

    private array $allowedSortColumns = [
        'assetNumber',
        'invoiceNumber',
        'name',
        'purchaseDate',
        'quantity',
        'serialNumber',
        'serialNumber2',
    ];

    private const JOIN_FILTERS = [
        'locationId' => [
            'table' => 'sfxon_position',
            'alias' => 'p',
            'condition' => 'd.position_id = p.id',
            'column' => 'p.location_id',
            'handler' => 'in',
        ],
        'manufacturerId' => [
            'table' => 'sfxon_device_type',
            'alias' => 'dt',
            'condition' => 'd.device_type_id = dt.id',
            'column' => 'dt.manufacturer_id',
            'handler' => 'in',
        ],
    ];

    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE_NAME, Device::class);
    }

    protected function getRelationSorts(): array {
        return [
            'deviceStatusId' => ['entity' => DeviceStatus::class, 'localKey' => 'device_status_id'],
            'deviceTypeId' => ['entity' => DeviceType::class, 'localKey' => 'device_type_id'],
            'itamUserId' => ['entity' => ItamUser::class, 'localKey' => 'itam_user_id'],
            'merchantId' => ['entity' => Merchant::class, 'localKey' => 'merchant_id'],
            'positionId' => ['entity' => Position::class, 'localKey' => 'position_id'],
        ];
    }
}