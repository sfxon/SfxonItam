<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Db\Position;
use OCA\SfxonItam\Validator\PositionValidator;
use OCP\AppFramework\Db\Entity;

class PositionService extends ItamServiceAbstract
{
    public function __construct(
        private readonly PositionValidator $positionValidator,)
    {
    }

    public function createNewEntity(): Entity
    {
        return new Position();
    }

    public function getDataFromRequest($requestArray, $expectedFields,)
    {
        return array_intersect_key(
            $requestArray,
            array_flip($expectedFields)
        );
    }

    public function validateData(array $data, ?int $excludeId = null)
    {
        return $this->positionValidator->validate($data, $excludeId);
    }
}