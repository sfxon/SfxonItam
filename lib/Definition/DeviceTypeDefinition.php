<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\DeviceType;
use OCA\SfxonItam\Db\DeviceTypeMapper;
use OCA\SfxonItam\Service\DeviceTypeService;

final class DeviceTypeDefinition extends EntityDefinition
{
    public const KEY = 'deviceType';

    public function __construct()
    {
        parent::__construct(
            key: self::KEY,
            label: 'DeviceType',
            route: 'device-type',
            entityClass: DeviceType::class,
            mapperClass: DeviceTypeMapper::class,
            serviceClass: DeviceTypeService::class,
            customFieldGroup: 'sfxon_device_type',
            listIncludes: ['manufacturer' => []],
            expectedFields: ['name', 'manufacturerId', 'comment'],
            deleteGuards: [
                new DeleteGuard(
                    DeviceMapper::class,
                    'device_type_id',
                    'Cannot delete. There are still devices assigned to this deviceType.'
                ),
            ],
        );
    }
}