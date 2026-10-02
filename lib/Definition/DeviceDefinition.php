<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

use OCA\SfxonItam\Db\Device;
use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Service\DeviceService;

final class DeviceDefinition extends EntityDefinition
{
    public const KEY = 'device';

    public function __construct()
    {
        parent::__construct(
            key: self::KEY,
            label: 'Device',
            route: 'device',
            entityClass: Device::class,
            mapperClass: DeviceMapper::class,
            serviceClass: DeviceService::class,
            customFieldGroup: 'sfxon_device',
            listIncludes: self::DEFAULT_LIST_INCLUDES,
            expectedFields: [
                'assetNumber',
                'deviceStatusId',
                'deviceTypeId',
                'description',
                'imageFileId',
                'invoiceNumber',
                'itamUserId',
                'merchantId',
                'name',
                'purchaseDate',
                'positionId',
                'quantity',
                'quantityUnitId',
                'serialNumber',
                'serialNumber2',
            ],
        );
    }
}