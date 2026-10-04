<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Db\QuantityUnit;
use OCA\SfxonItam\Validator\QuantityUnitValidator;
use OCP\AppFramework\Db\Entity;

class QuantityUnitService extends ItamServiceAbstract
{
    public function __construct(
        private readonly QuantityUnitValidator $quantityUnitValidator,)
    {
    }

    
    public function createNewEntity(): Entity
    {
        return new QuantityUnit();
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
        return $this->quantityUnitValidator->validate($data, $excludeId);
    }
}