<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Db\Location;
use OCA\SfxonItam\Validator\LocationValidator;
use OCP\AppFramework\Db\Entity;

class LocationService extends ItamServiceAbstract
{
    public function __construct(
        private readonly LocationValidator $locationValidator,)
    {
    }

    public function createNewEntity(): Entity
    {
        return new Location();
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
        return $this->locationValidator->validate($data, $excludeId);
    }
}