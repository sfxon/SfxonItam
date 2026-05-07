<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string|null getName()
 * @method void setName(string|null $name)
 * @method int|null getManufacturerId()
 * @method void setManufacturerId(int|null $manufacturerId)
 * @method string|null getComment()
 * @method void setComment(string|null $comment)
 */
class DeviceType extends Entity implements \JsonSerializable {
    protected ?string $name = null;
    protected ?int $manufacturerId = null;
    protected ?string $comment = null;

    public function __construct() {
        $this->addType('id', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'manufacturerId' => $this->getManufacturerId(),
            'comment' => $this->getComment()
        ];
    }
}