<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\Position;
use OCA\SfxonItam\Db\PositionMapper;
use OCA\SfxonItam\Service\PositionService;

final class PositionDefinition extends EntityDefinition
{
    public const KEY = 'position';

    public function __construct()
    {
        parent::__construct(
            key: self::KEY,
            label: 'Position',
            route: 'position',
            entityClass: Position::class,
            mapperClass: PositionMapper::class,
            serviceClass: PositionService::class,
            customFieldGroup: 'sfxon_position',
            expectedFields: ['name', 'locationId', 'comment'],
            listIncludes: array_merge(self::DEFAULT_LIST_INCLUDES, ['location' => []]),
            deleteGuards: [
                new DeleteGuard(
                    DeviceMapper::class,
                    'position_id',
                    'Cannot delete. There are still devices assigned to this position.'
                ),
            ],
        );
    }
}