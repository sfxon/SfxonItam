<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string|null getFirstname()
 * @method void setFirstname(string|null $firstname)
 * @method string|null getLastname()
 * @method void setLastname(string|null $lastname)
 * @method string|null getEmail()
 * @method void setEmail(string|null $email)
 * @method string|null getComment()
 * @method void setComment(string|null $comment)
 */
class ItamUser extends Entity implements \JsonSerializable {
    use TEntityWithCustomFields;

    protected ?string $firstname = null;
    protected ?string $lastname = null;
    protected ?string $email = null;
    protected ?string $comment = null;

    public function __construct() {
        $this->addType('id', 'integer');
    }

    protected function getDefaultSortField(): string {
        return 'email';
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
                'label' => 'Firstname',
                'length' => 300,
                'name' => 'firstname',
                'propertyName' => 'firstname',
                'type' => 'VARCHAR',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'filterType' => 'like',
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Lastname',
                'length' => 300,
                'name' => 'lastname',
                'propertyName' => 'lastname',
                'type' => 'VARCHAR',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'filterType' => 'like',
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Email',
                'length' => 300,
                'name' => 'email',
                'propertyName' => 'email',
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