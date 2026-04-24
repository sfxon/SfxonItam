<?php
declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Validator\DeviceValidator;

class DeviceService {
    public function __construct(
        private readonly DeviceValidator $deviceValidator
    ) {
    }

    public function validateDeviceData(array $data) {
        return $this->deviceValidator->validate($data);
    }
}