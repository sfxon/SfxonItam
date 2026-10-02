<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\QuantityUnit;
use OCA\SfxonItam\Db\QuantityUnitMapper;
use OCA\SfxonItam\Service\QuantityUnitService;

final class QuantityUnitDefinition extends EntityDefinition
{
    public const KEY = 'quantityUnit';

    public function __construct()
    {
        parent::__construct(
            key: self::KEY,
            label: 'QuantityUnit',
            route: 'quantity-unit',
            entityClass: QuantityUnit::class,
            mapperClass: QuantityUnitMapper::class,
            serviceClass: QuantityUnitService::class,
            customFieldGroup: 'sfxon_quantity_unit',
            expectedFields: ['name', 'comment'],
            deleteGuards: [
                new DeleteGuard(
                    DeviceMapper::class,
                    'quantity_unit_id',
                    'Cannot delete. There are still devices assigned to this quantityUnit.'
                ),
            ],
        );
    }
}