<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Validator\ItamUserValidator;

class ItamUserService
{
    public function __construct(
        private readonly ItamUserValidator $itamUserValidator,)
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
        return $this->itamUserValidator->validate($data, $excludeId);
    }
}