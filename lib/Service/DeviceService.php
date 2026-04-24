<?php
declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Validator\DeviceValidator;

class DeviceService {
    public function __construct(
        private readonly DeviceValidator $deviceValidator
    ) {
    }

    public function getDataFromRequest($requestArray, $expectedFields) {
        return array_intersect_key(
            $requestArray,
            array_flip($expectedFields)
        );
    }

    public function validateDeviceData(array $data) {
        return $this->deviceValidator->validate($data);
    }
}