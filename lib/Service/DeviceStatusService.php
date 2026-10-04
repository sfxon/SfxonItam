<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Db\DeviceStatus;
use OCA\SfxonItam\Validator\DeviceStatusValidator;
use OCP\AppFramework\Db\Entity;

class DeviceStatusService extends ItamServiceAbstract
{
    public function __construct(
        private readonly DeviceStatusValidator $deviceStatusValidator,)
    {
    }

    public function createNewEntity(): Entity
    {
        return new DeviceStatus();
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
        return $this->deviceStatusValidator->validate($data, $excludeId);
    }
}