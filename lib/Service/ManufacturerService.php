<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Validator\ManufacturerValidator;

class ManufacturerService
{
    public function __construct(
        private readonly ManufacturerValidator $manufacturerValidator,)
    {
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
        return $this->manufacturerValidator->validate($data, $excludeId);
    }
}