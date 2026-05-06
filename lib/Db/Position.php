<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string|null getName()
 * @method void setName(string|null $name)
 * @method int|null getLocationId()
 * @method void setLocationId(int|null $locationId)
 * @method string|null getComment()
 * @method void setComment(string|null $comment)
 */
class Position extends Entity implements \JsonSerializable {
    protected ?string $name = null;
    protected ?int $locationId = null;
    protected ?string $comment = null;

    public function __construct() {
        $this->addType('id', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'locationId' => $this->getLocationId(),
            'comment' => $this->getComment()
        ];
    }
}