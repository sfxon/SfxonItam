<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Db\CustomField;
use OCA\SfxonItam\Db\CustomFieldMapper;
use OCA\SfxonItam\Db\CustomFieldGroupMapper;
use OCA\SfxonItam\Validator\CustomFieldDataValidator;
use OCA\SfxonItam\Validator\CustomFieldValidator;

// At the moment this class provides services for the customField entity as well as services for operations on custom field data.
// I really had a discussion with myself about it, you know, I know I should refactor it.
// Maybe I do, if it grows over a certain size or complexity. But for now, it does what it should, so I will keep it like that.
// Or I should really refactor it for better clarity. But I am lazy and I cannot come up with a proper naming.
// It's 31 degrees celcius out there and I am melting even in the semi-basement office.
// Maybe I let that comment about this stupid design decision here. I really don't want to bother that ai again. Hmpfff.
class CustomFieldService {
    public function __construct(
        private readonly CustomFieldGroupMapper $customFieldGroupMapper,
        private readonly CustomFieldMapper $customFieldMapper,
        private readonly CustomFieldDataValidator $customFieldDataValidator,
        private readonly CustomFieldValidator $customFieldValidator,)
    {
    }

    public function createCustomField(array $data)
    {
        // Lade die CustomFieldGroup, um den entityName zu ermitteln
        $group = $this->customFieldGroupMapper->findById((int)$data['customFieldGroupId']);

        if ($group === null) {
            throw new \InvalidArgumentException('CustomFieldGroup not found.');
        }

        $type = $data['type'];
        $options = $data['options'] ?? null;

        $customField = new CustomField();
        $customField->setCustomFieldGroupId((int)$data['customFieldGroupId']);
        $customField->setTechnicalName($data['technicalName']);
        $customField->setName($data['name']);
        $customField->setType($type);
        $customField->setPosition((int)$data['position']);
        $customField->setEditable((bool)($data['editable'] ?? true));
        $customField->setOptions($options !== null ? json_encode($options) : null);
        $customField->setValidation(isset($data['validation']) ? json_encode($data['validation']) : null);
        $customField->setComment($data['comment'] ?? null);

        $this->customFieldMapper->addColumnToEntityTable(
            $group->getEntityName(),
            'custom_' . $data['technicalName'],
            $type,
            $options
        );

        return $this->customFieldMapper->insert($customField);
    }

    public function deleteCustomField(int $id): void
    {
        $customField = $this->customFieldMapper->findById($id);

        $group = $this->customFieldGroupMapper->findById(
            (int)$customField->getCustomFieldGroupId()
        );

        if ($group === null) {
            throw new \InvalidArgumentException('CustomFieldGroup not found.');
        }

        $columnName = 'custom_' . $customField->getTechnicalName();

        $this->customFieldMapper->dropColumnFromEntityTable(
            $group->getEntityName(),
            $columnName
        );

        $this->customFieldMapper->delete($customField);
    }

    // This method filters out all fields, that are not expected, and only returns the one that we really want.
    public function getCustomFieldDataFromRequest($customFieldDefinitions, $requestArray)
    {
        // Enumerate all available technicalNames for the customFields.
        $postedCustomFields = $requestArray['customFields'];
        $validCustomFields = [];

        foreach($customFieldDefinitions as $customFieldDefinition) {
            // Cannot use isset here, because isset would return false, even if the array key exists, but the value of the field index is null.
            // The array_key_exists function instead returns true, when the array key exists, even if its value is null.
            if(array_key_exists($customFieldDefinition['technicalName'], $postedCustomFields)) {
                $validCustomFields[$customFieldDefinition['technicalName']] = $postedCustomFields[$customFieldDefinition['technicalName']];
            }
        }

        return $validCustomFields;
    }

    public function getCustomFieldsDefinitionByGroup(): array
    {
        $customFields = [];
        $customFieldGroup = $this->customFieldGroupMapper->findByEntityName('sfxon_device');

        if ($customFieldGroup !== null) {
            $result = $this->customFieldMapper->searchPaged(
                $customFieldGroup->getId(),
                'position',
                'ASC',
                1000
            );
            $customFields = $result['mainData'];
        }

        return $customFields;
    }

    public function getDataFromRequest($requestArray, $expectedFields,)
    {
        return array_intersect_key(
            $requestArray,
            array_flip($expectedFields)
        );
    }

    public function validateCustomFieldData(array $customFieldDefinitions, array $customFieldData): array
    {
        $errors = [];

        foreach ($customFieldDefinitions as $customFieldDefinition) {
            if (!isset($customFieldDefinition['technicalName'])) {
                throw new \Exception('Missing custom field technical name.');
            }

            $customFieldName = $customFieldDefinition['technicalName'];

            // Value may legitimately be absent from the submitted data.
            $customFieldValue = array_key_exists($customFieldName, $customFieldData)
                ? $customFieldData[$customFieldName]
                : null;

            if (!isset($customFieldDefinition['type'])) {
                throw new \Exception('Missing custom field type.');
            }

            $customFieldType = $customFieldDefinition['type'];

            if (!isset($customFieldDefinition['validation'])) {
                throw new \Exception('Missing validation information found for custom field.');
            }

            if (!isset($customFieldDefinition['options'])) {
                throw new \Exception('Missing options information found for custom field.');
            }

            $validation = json_decode($customFieldDefinition['validation'], true);
            $options = json_decode($customFieldDefinition['options'], true);

            switch ($customFieldType) {
                case 'boolean':
                    $fieldErrors = $this->customFieldDataValidator->validateBoolean($customFieldName, $validation, $customFieldValue);
                    break;
                case 'date':
                    $fieldErrors = $this->customFieldDataValidator->validateDate($customFieldName, $validation, $customFieldValue);
                    break;
                case 'datetime':
                    $fieldErrors = $this->customFieldDataValidator->validateDatetime($customFieldName, $validation, $customFieldValue);
                    break;
                case 'decimal':
                    $fieldErrors = $this->customFieldDataValidator->validateDecimal($customFieldName, $options, $validation, $customFieldValue);
                    break;
                case 'file':
                    $fieldErrors = $this->customFieldDataValidator->validateFile($customFieldName, $options, $validation, $customFieldValue);
                    break;
                case 'foreign_key':
                    $fieldErrors = $this->customFieldDataValidator->validateForeignKey($customFieldName, $options, $validation, $customFieldValue);
                    break;
                case 'integer':
                    $fieldErrors = $this->customFieldDataValidator->validateInteger($customFieldName, $validation, $customFieldValue);
                    break;
                case 'longtext':
                    $fieldErrors = $this->customFieldDataValidator->validateLongtext($customFieldName, $validation, $customFieldValue);
                    break;
                default:
                    throw new \Exception('No validator found for custom field type ' . $customFieldType);
            }

            if (!empty($fieldErrors)) {
                $errors = array_merge($errors, $fieldErrors);
            }
        }

        var_dump($errors);

        return $errors;
    }

    public function validateData(array $data, ?int $excludeId = null)
    {
        return $this->customFieldValidator->validate($data, $excludeId);
    }

    public function validateUpdateData(array $data, ?int $excludeId = null)
    {
        return $this->customFieldValidator->validateUpdate($data, $excludeId);
    }
}