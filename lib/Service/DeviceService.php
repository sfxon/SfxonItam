<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Db\Device;
use OCA\SfxonItam\Validator\DeviceValidator;
use OCP\AppFramework\Db\Entity;

class DeviceService extends ItamServiceAbstract
{
    public function __construct(
        private readonly DeviceValidator $deviceValidator,)
    {
    }

    public function createNewEntity(): Entity
    {
        return new Device();
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
        return $this->deviceValidator->validate($data, $excludeId);
    }
}