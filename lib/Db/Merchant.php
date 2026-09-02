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
    use TEntityWithCustomFields;

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
                'filterType' => 'none',
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Identifier',
                'length' => 20,
                'name' => 'id',
                'propertyName' => 'id',
                'type' => 'BIGINT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => true,
                'unique' => true,
            ],
            [
                'defaultValue' => NULL,
                'filterType' => 'like',
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Name',
                'length' => 300,
                'name' => 'name',
                'propertyName' => 'name',
                'type' => 'VARCHAR',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => true,
            ],
            [
                'defaultValue' => NULL,
                'filterType' => 'none',
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Comment',
                'length' => null,
                'name' => 'comment',
                'propertyName' => 'comment',
                'type' => 'TEXT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
        ];
    }

    /**
     * @param mixed $customFields array<int, array{technicalName?: string}>|null
     * @return array<string, mixed>
     */
    public function jsonSerialize(mixed $customFields = null): array {
        return $this->jsonSerializeFields($customFields);
    }
}