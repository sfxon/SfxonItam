<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

use OCA\SfxonItam\Db\DeviceTypeMapper;
use OCA\SfxonItam\Db\Manufacturer;
use OCA\SfxonItam\Db\ManufacturerMapper;
use OCA\SfxonItam\Service\ManufacturerService;

final class ManufacturerDefinition extends EntityDefinition
{
    public const KEY = 'manufacturer';

    public function __construct()
    {
        parent::__construct(
            key: self::KEY,
            label: 'Manufacturer',
            route: 'manufacturer',
            entityClass: Manufacturer::class,
            mapperClass: ManufacturerMapper::class,
            serviceClass: ManufacturerService::class,
            customFieldGroup: 'sfxon_manufacturer',
            expectedFields: ['name', 'comment'],
            deleteGuards: [
                new DeleteGuard(
                    DeviceTypeMapper::class,
                    'manufacturer_id',
                    'Cannot delete. There are still deviceTypes assigned to this manufacturer.'
                ),
            ],
        );
    }
}