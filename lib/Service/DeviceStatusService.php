<?php
declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Validator\DeviceStatusValidator;

class DeviceStatusService {
    public function __construct(
        private readonly DeviceStatusValidator $deviceStatusValidator
    ) {
    }

    public function getDataFromRequest($requestArray, $expectedFields) {
        return array_intersect_key(
            $requestArray,
            array_flip($expectedFields)
        );
    }

    public function validateData(array $data) {
        return $this->deviceStatusValidator->validate($data);
    }
}