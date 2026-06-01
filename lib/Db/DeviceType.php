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

    /**
     * Field definitions are used to check input and create automatic forms.
     */
    public static function getFieldDefinition() {
        return [
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Identifier',
                'length' => 20,
                'name' => 'id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => true,
                'unique' => true,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Name',
                'length' => 300,
                'name' => 'name',
                'type' => 'VARCHAR',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => true,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => 'manufacturer',
                'index' => true,
                'label' => 'Manufacturer',
                'length' => null,
                'name' => 'manufacturer_id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Comment',
                'length' => null,
                'name' => 'comment',
                'type' => 'TEXT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
        ];
    }

    /**
     * @TODO: Check, if this could be changed. It could use the definition of "getFieldDefinition", to serialize the data.
     */
    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'manufacturerId' => $this->getManufacturerId(),
            'comment' => $this->getComment()
        ];
    }
}