<?php declare(strict_types=1);

// Create by Migration: Version010100Date20260511130000
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string|null getName()
 * @method void setName(string|null $name)
 * @method string|null getComment()
 * @method void setComment(string|null $comment)
 */
class QuantityUnit extends Entity implements \JsonSerializable {
    protected ?string $name = null;
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

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'comment' => $this->getComment()
        ];
    }
}