<?php
declare(strict_types=1);
namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string|null getCustomFieldGroupId()
 * @method void setCustomFieldGroupId(string|null $customFieldGroupId)
 * @method string|null getTechnicalName()
 * @method void setTechnicalName(string|null $technicalName)
 * @method string|null getName()
 * @method void setName(string|null $name)
 * @method string|null getType()
 * @method void setType(string|null $type)
 * @method int getPosition()
 * @method void setPosition(string $position = 0)
 * @method string|null getOptions()
 * @method void setOptions(string|null $options)
 * @method bool getEditable()
 * @method void setEditable(bool $editable)
 * @method string|null getValidation()
 * @method void setValidation(string|null $validation)
 * @method string|null getComment()
 * @method void setComment(string|null $comment)
 */
class CustomField extends Entity implements \JsonSerializable {
    protected ?string $customFieldGroupId = null;
    protected ?string $technicalName = null;
    protected ?string $name = null;
    protected ?string $type = null;
    protected ?int $position = 0;
    protected ?string $options = null;
    protected ?bool $editable = null;
    protected ?string $validation = null;
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
                'label' => 'Technical Name',
                'length' => 32,
                'name' => 'technical_name',
                'type' => 'VARCHAR',
                'requiredOnCreate' => true,
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
                'label' => 'Type',
                'length' => 16,
                'name' => 'type',
                'type' => 'VARCHAR',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => false,
            ],
            [
                'defaultValue' => 0,
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Position',
                'length' => 11,
                'name' => 'position',
                'type' => 'INT',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Optionen',
                'length' => null,
                'name' => 'options',
                'type' => 'JSON',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => true,
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Editable',
                'length' => null,
                'name' => 'editable',
                'type' => 'BOOL',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'defaultValue' => NULL,
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Validation',
                'length' => null,
                'name' => 'validation',
                'type' => 'JSON',
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
            'customFieldGroupId' => $this->getCustomFieldGroupId(),
            'technicalName' => $this->getTechnicalName(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'position' => $this->getPosition(),
            'options' =>  $this->getOptions(),
            'editable' => $this->getEditable(),
            'validation' => $this->getValidation(),
            'comment' => $this->getComment()
        ];
    }
}