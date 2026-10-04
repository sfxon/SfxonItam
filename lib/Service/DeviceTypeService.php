<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Db\DeviceType;
use OCA\SfxonItam\Validator\DeviceTypeValidator;
use OCP\AppFramework\Db\Entity;

class DeviceTypeService extends ItamServiceAbstract
{
    public function __construct(
        private readonly DeviceTypeValidator $deviceTypeValidator,)
    {
    }

    public function createNewEntity(): Entity
    {
        return new DeviceType();
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
        return $this->deviceTypeValidator->validate($data, $excludeId);
    }
}