<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\DeviceStatus;
use OCA\SfxonItam\Db\DeviceStatusMapper;
use OCA\SfxonItam\Service\DeviceStatusService;

final class DeviceStatusDefinition extends EntityDefinition
{
    public const KEY = 'deviceStatus';

    public function __construct()
    {
        parent::__construct(
            key: self::KEY,
            label: 'Device Status',
            route: 'device-status',
            entityClass: DeviceStatus::class,
            mapperClass: DeviceStatusMapper::class,
            serviceClass: DeviceStatusService::class,
            customFieldGroup: 'sfxon_device_status',
            listIncludes: self::DEFAULT_LIST_INCLUDES,
            expectedFields: ['name', 'comment'],
            deleteGuards: [
                new DeleteGuard(
                    DeviceMapper::class,
                    'device_status_id',
                    'Cannot delete. There are still devices assigned to this status.'
                ),
            ],
        );
    }
}