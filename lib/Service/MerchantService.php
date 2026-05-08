<?php
declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Validator\MerchantValidator;

class MerchantService {
    public function __construct(
        private readonly MerchantValidator $merchantValidator
    ) {
    }

    public function getDataFromRequest($requestArray, $expectedFields) {
        return array_intersect_key(
            $requestArray,
            array_flip($expectedFields)
        );
    }

    public function validateData(array $data) {
        return $this->merchantValidator->validate($data);
    }
}