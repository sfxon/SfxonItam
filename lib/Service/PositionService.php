<?php
declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Validator\PositionValidator;

class PositionService {
    public function __construct(
        private readonly PositionValidator $positionValidator
    ) {
    }

    public function getDataFromRequest($requestArray, $expectedFields) {
        return array_intersect_key(
            $requestArray,
            array_flip($expectedFields)
        );
    }

    public function validateData(array $data) {
        return $this->positionValidator->validate($data);
    }
}