<?php
declare(strict_types=1);
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
    protected ?string $firstname = null;
    protected ?string $lastname = null;
    protected ?string $email = null;
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
                'label' => 'Firstname',
                'length' => 300,
                'name' => 'firstname',
                'type' => 'VARCHAR',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Lastname',
                'length' => 300,
                'name' => 'lastname',
                'type' => 'VARCHAR',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Email',
                'length' => 300,
                'name' => 'email',
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

    /**
     * @TODO: Check, if this could be changed. It could use the definition of "getFieldDefinition", to serialize the data.
     */
    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'email' => $this->getEmail(),
            'comment' => $this->getComment()
        ];
    }
}