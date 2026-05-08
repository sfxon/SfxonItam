<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string|null getName()
 * @method void setName(string|null $name)
 * @method string|null getComment()
 * @method void setComment(string|null $comment)
 */
class Merchant extends Entity implements \JsonSerializable {
    protected ?string $name = null;
    protected ?string $comment = null;

    public function __construct() {
        $this->addType('id', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'comment' => $this->getComment()
        ];
    }
}