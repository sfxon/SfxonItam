<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\Merchant;
use OCA\SfxonItam\Db\MerchantMapper;
use OCA\SfxonItam\Service\MerchantService;

final class MerchantDefinition extends EntityDefinition
{
    public const KEY = 'merchant';

    public function __construct()
    {
        parent::__construct(
            key: self::KEY,
            label: 'Merchant',
            route: 'merchant',
            entityClass: Merchant::class,
            mapperClass: MerchantMapper::class,
            serviceClass: MerchantService::class,
            customFieldGroup: 'sfxon_merchant',
            expectedFields: ['name', 'comment'],
            deleteGuards: [
                new DeleteGuard(
                    DeviceMapper::class,
                    'merchant_id',
                    'Cannot delete. There are still devices assigned to this merchant.'
                ),
            ],
        );
    }
}