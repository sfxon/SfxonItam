<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Db\CustomField;
use OCA\SfxonItam\Db\CustomFieldMapper;
use OCA\SfxonItam\Db\CustomFieldGroupMapper;
use OCA\SfxonItam\Validator\CustomFieldValidator;

class CustomFieldService {
    public function __construct(
        private readonly CustomFieldGroupMapper $customFieldGroupMapper,
        private readonly CustomFieldMapper $customFieldMapper,
        private readonly CustomFieldValidator $customFieldValidator,) {
    }

    public function createCustomField(array $data) {
        // Lade die CustomFieldGroup, um den entityName zu ermitteln
        $group = $this->customFieldGroupMapper->findById((int)$data['customFieldGroupId']);

        if ($group === null) {
            throw new \InvalidArgumentException('CustomFieldGroup not found.');
        }

        $customField = new CustomField();
        $customField->setCustomFieldGroupId((int)$data['customFieldGroupId']);
        $customField->setTechnicalName($data['technicalName']);
        $customField->setName($data['name']);
        $customField->setType($data['type']);
        $customField->setPosition((int)$data['position']);
        $customField->setEditable((bool)($data['editable'] ?? true));
        $customField->setValidation(isset($data['validation']) ? json_encode($data['validation']) : null);
        $customField->setComment($data['comment'] ?? null);

        // Spalte in der Zieltabelle anlegen
        $this->customFieldMapper->addColumnToEntityTable(
            $group->getEntityName(),
            'custom_' . $data['technicalName'],
            $data['type']
        );

        return $this->customFieldMapper->insert($customField);
    }

    public function getDataFromRequest($requestArray, $expectedFields,)
    {
        return array_intersect_key(
            $requestArray,
            array_flip($expectedFields)
        );
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