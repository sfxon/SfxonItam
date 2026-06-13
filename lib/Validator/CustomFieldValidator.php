<?php declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCA\SfxonItam\Db\CustomFieldGroupMapper;
use OCA\SfxonItam\Db\CustomFieldMapper;
use OCP\IL10N;

class CustomFieldValidator {
    public function __construct(
        private IL10N $l,
        private CustomFieldGroupMapper $customFieldGroupMapper,
        private CustomFieldMapper $customFieldMapper,)
    {
    }

    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // a) name: at least 1 signs.
        if (empty($data['name'])) {
            $errors['name'] = $this->l->t('The field "name" is required.');
        } elseif (mb_strlen(trim($data['name'])) < 1) {
            $errors['name'] = $this->l->t('The name must be at least 1 character long.');
        } else {
            // b) name: must be unique.
            $existing = $this->customFieldMapper->findByNameAndCustomFieldGroupId(trim($data['name']), intval($data['customFieldGroupId']));

            if ($existing !== null && $existing->getId() !== $excludeId) {
                $errors['name'] = $this->l->t('A custom field with this name already exists in this custom field group.');
            }
        }

        // c) Technical Name
        if (empty($data['technicalName'])) {
            $errors['technicalName'] = $this->l->t('The field technical name is required.');
        } elseif (mb_strlen(trim($data['name'])) < 1) {
            $errors['technicalName'] = $this->l->t('The technical name must be at least 1 character long.');
        }  elseif (mb_strlen(trim($data['name'])) > 32) {
            $errors['technicalName'] = $this->l->t('The technical name must not be more than 32 characters long.');
        } else {
            // d) name: must be unique.
            $existing = $this->customFieldMapper->findByTechnicalNameAndCustomFieldGroupId(trim($data['technicalName']), intval($data['customFieldGroupId']));

            if ($existing !== null && $existing->getId() !== $excludeId) {
                $errors['technicalName'] = $this->l->t('A custom field with this technical name already exists in this custom field group.');
            }
        }

        // e) Check, if custom field group exists.
        $existing = $this->customFieldGroupMapper->findById(intval($data['customFieldGroupId']));

        if ($existing !== null && $existing->getId() !== $excludeId) {
            $errors['customFieldGroupId'] = $this->l->t('A custom field with this technical name already exists.');
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}