<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

use OCA\SfxonItam\Db\Location;
use OCA\SfxonItam\Db\LocationMapper;
use OCA\SfxonItam\Db\PositionMapper;
use OCA\SfxonItam\Service\LocationService;

final class LocationDefinition extends EntityDefinition
{
    public const KEY = 'location';

    public function __construct()
    {
        parent::__construct(
            key: self::KEY,
            label: 'Location',
            route: 'location',
            entityClass: Location::class,
            mapperClass: LocationMapper::class,
            serviceClass: LocationService::class,
            customFieldGroup: 'sfxon_location',
            expectedFields: ['name', 'comment'],
            deleteGuards: [
                new DeleteGuard(
                    PositionMapper::class,
                    'location_id',
                    'Cannot delete. There are still positions assigned to this location.'
                ),
            ],
        );
    }
}