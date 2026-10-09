<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\ItamUser;
use OCA\SfxonItam\Db\ItamUserMapper;
use OCA\SfxonItam\Service\ItamUserService;

final class ItamUserDefinition extends EntityDefinition
{
    public const KEY = 'itamUser';

    public function __construct()
    {
        parent::__construct(
            key: self::KEY,
            label: 'ItamUser',
            route: 'itam-user',
            entityClass: ItamUser::class,
            mapperClass: ItamUserMapper::class,
            serviceClass: ItamUserService::class,
            customFieldGroup: 'sfxon_itam_user',
            listIncludes: self::DEFAULT_LIST_INCLUDES,
            expectedFields: ['firstname', 'lastname', 'email', 'comment'],
            defaultOrderBy: 'email',
            deleteGuards: [
                new DeleteGuard(
                    DeviceMapper::class,
                    'itam_user_id',
                    'Cannot delete. There are still devices assigned to this itamUser.'
                ),
            ],
            labelFields: ['firstname', 'lastname'],
            searchFields: ['firstname', 'lastname', 'email'],
        );
    }
}