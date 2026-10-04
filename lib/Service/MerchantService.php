<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Db\Merchant;
use OCA\SfxonItam\Validator\MerchantValidator;
use OCP\AppFramework\Db\Entity;

class MerchantService extends ItamServiceAbstract
{
    public function __construct(
        private readonly MerchantValidator $merchantValidator)
    {
    }

    public function createNewEntity(): Entity
    {
        return new Merchant();
    }

    public function getDataFromRequest($requestArray, $expectedFields)
    {
        return array_intersect_key(
            $requestArray,
            array_flip($expectedFields)
        );
    }

    public function validateData(array $data, ?int $excludeId = null)
    {
        return $this->merchantValidator->validate($data, $excludeId);
    }
}