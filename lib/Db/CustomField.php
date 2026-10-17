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
        $this->addType('editable', 'boolean');
    }

    /**
     * Field definitions are used to check input and create automatic forms.
     */
    public static function getFieldDefinition() {
        return [
            [
                'dbType' => 'BIGINT',
                'defaultValue' => NULL,
                'filterType' => 'none',
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Identifier',
                'length' => 20,
                'name' => 'id',
                'propertyName' => 'id',
                'type' => 'id',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => true,
                'unique' => true,
            ],
            [
                'dbType' => 'VARCHAR',
                'defaultValue' => NULL,
                'filterType' => 'like',
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Technical Name',
                'length' => 32,
                'name' => 'technical_name',
                'propertyName' => 'technicalName',
                'type' => 'string',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => true,
            ],
            [
                'dbType' => 'VARCHAR',
                'defaultValue' => NULL,
                'filterType' => 'like',
                'foreignEntity' => false,
                'index' => true,
                'label' => 'Name',
                'length' => 300,
                'name' => 'name',
                'propertyName' => 'name',
                'type' => 'string',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => true,
            ],
            [
                'dbType' => 'VARCHAR',
                'defaultValue' => NULL,
                'filterType' => 'like',
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Type',
                'length' => 16,
                'name' => 'type',
                'propertyName' => 'type',
                'type' => 'string',
                'requiredOnCreate' => true,
                'requiredOnUpdate' => true,
                'unique' => false,
            ],
            [
                'dbType' => 'INT',
                'defaultValue' => 0,
                'filterType' => 'in',
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Position',
                'length' => 11,
                'name' => 'position',
                'propertyName' => 'position',
                'type' => 'int',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'dbType' => 'JSON',
                'defaultValue' => NULL,
                'filterType' => 'none',
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Optionen',
                'length' => null,
                'name' => 'options',
                 'propertyName' => 'options',
                'type' => 'json',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'dbType' => 'BOOL',
                'defaultValue' => true,
                'filterType' => 'none',
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Editable',
                'length' => null,
                'name' => 'editable',
                'propertyName' => 'editable',
                'type' => 'bool',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'dbType' => 'JSON',
                'defaultValue' => NULL,
                'filterType' => 'none',
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Validation',
                'length' => null,
                'name' => 'validation',
                'propertyName' => 'validation',
                'type' => 'json',
                'requiredOnCreate' => false,
                'requiredOnUpdate' => false,
                'unique' => false,
            ],
            [
                'dbType' => 'TEXT',
                'defaultValue' => NULL,
                'filterType' => 'none',
                'foreignEntity' => false,
                'index' => false,
                'label' => 'Comment',
                'length' => null,
                'name' => 'comment',
                'propertyName' => 'comment',
                'type' => 'text',
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