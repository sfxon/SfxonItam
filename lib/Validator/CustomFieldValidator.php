<?php declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCA\SfxonItam\Db\CustomFieldGroupMapper;
use OCA\SfxonItam\Db\CustomFieldMapper;
use OCA\SfxonItam\ForeignKey\ForeignKeyRegistry;
use OCP\IL10N;

class CustomFieldValidator {
    private const DECIMAL_MAX_PRECISION = 65;
    private const DECIMAL_MAX_SCALE = 30;
    private const STRING_MIN_LENGTH = 1;
    private const STRING_MAX_LENGTH = 4096;
    private const TEXT_MIN_LENGTH = 1;
    private const TEXT_MAX_LENGTH = 65535;
    

    public function __construct(
        private IL10N $l,
        private CustomFieldGroupMapper $customFieldGroupMapper,
        private CustomFieldMapper $customFieldMapper,)
    {
    }

    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = $this->validateName($data, $excludeId, []);

        // c) Technical Name
        if (empty($data['technicalName'])) {
            $errors['technicalName'] = $this->l->t('The field technical name is required.');
        } elseif (mb_strlen(trim($data['name'])) < 1) {
            $errors['technicalName'] = $this->l->t('The technical name must be at least 1 character long.');
        }  elseif (mb_strlen(trim($data['name'])) > 32) {
            $errors['technicalName'] = $this->l->t('The technical name must not be more than 32 characters long.');
        } elseif (!preg_match('/^[a-z_][a-z0-9_]*$/', trim($data['technicalName']))) {
            $errors['technicalName'] = $this->l->t('The technical name must only contain lowercase letters, underscores, and numbers (not at the beginning).');
        } else {
            // d) name: must be unique.
            $existing = $this->customFieldMapper->findByTechnicalNameAndCustomFieldGroupId(trim($data['technicalName']), intval($data['customFieldGroupId']));

            if ($existing !== null && $existing->getId() !== $excludeId) {
                $errors['technicalName'] = $this->l->t('A custom field with this technical name already exists in this custom field group.');
            }
        }

        // e) Check, if custom field group exists.
        $existing = $this->customFieldGroupMapper->findById(intval($data['customFieldGroupId']));

        if ($existing === null) {
            $errors['customFieldGroupId'] = $this->l->t('CustomFieldGroupId not found.');
        }

        // f) Check type
        $type = $data['type'];

        if(trim($type) === '') {
            $errors['type'] = $this->l->t('A type must be selected.');
        } else if(
            $type !== 'string' &&
            $type !== 'int' &&
            $type !== 'decimal' &&
            $type !== 'bool' &&
            $type !== 'imageFile' &&
            $type !== 'text' &&
            $type !== 'date' &&
            $type !== 'datetime' &&
            $type !== 'foreignKey'
        ) {
            $errors['type'] = $this->l->t('This type is not supported.');
        }

        // g) Check string.
        if ($type === 'string') {
            $errors = $this->validateMaxLengthOptions($data, $errors, 'string', self::STRING_MIN_LENGTH, self::STRING_MAX_LENGTH);
            $errors = $this->validateMinLengthValidation($data, $errors, 'string', self::STRING_MIN_LENGTH, self::STRING_MAX_LENGTH);
        }

        // h) Check decimal options
        if ($type === 'decimal') {
            $errors = $this->validateDecimalOptions($data, $errors);
        }

        // i) Validate text.
        if ($type === 'text') {
            $errors = $this->validateMaxLengthOptions($data, $errors, 'text', self::TEXT_MIN_LENGTH, self::TEXT_MAX_LENGTH);
            $errors = $this->validateMinLengthValidation($data, $errors, 'text', self::TEXT_MIN_LENGTH, self::TEXT_MAX_LENGTH);
        }

        // j) Validate foreign key options.
        if ($type === 'foreignKey') {
            $errors = $this->validateForeignKeyOptions($data, $errors);
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    private function isValidDigitLength(mixed $value, int $min, int $max): bool
    {
        if ($value === '' || $value === null) {
            return false;
        }

        if (!preg_match('/^\d+$/', (string) $value)) {
            return false;
        }

        $intValue = (int) $value;

        return $intValue >= $min && $intValue <= $max;
    }

    private function validateDecimalOptions(array $data, array $errors): array
    {
        $options = $data['options']['decimal'] ?? null;

        if (!is_array($options)) {
            $errors['optionsIntegerDigitsLength'] = $this->l->t('A number between 0 and %s must be defined.', [self::DECIMAL_MAX_PRECISION]);
            $errors['optionsFractionDigitsLength'] = $this->l->t('A number between 0 and %s must be defined.', [self::DECIMAL_MAX_SCALE]);

            return $errors;
        }

        $integerRaw  = $options['integerDigitsLength'] ?? '';
        $fractionRaw = $options['fractionDigitsLength'] ?? '';

        $integerValid  = $this->isValidDigitLength($integerRaw, 0, self::DECIMAL_MAX_PRECISION);
        $fractionValid = $this->isValidDigitLength($fractionRaw, 0, self::DECIMAL_MAX_SCALE);

        if (!$integerValid) {
            $errors['optionsIntegerDigitsLength'] = $this->l->t(
                'Integer digits must be a whole number between 0 and %s.',
                [self::DECIMAL_MAX_PRECISION]
            );
        }

        if (!$fractionValid) {
            $errors['optionsFractionDigitsLength'] = $this->l->t(
                'Fraction digits must be a whole number between 0 and %s.',
                [self::DECIMAL_MAX_SCALE]
            );
        }

        if ($integerValid && $fractionValid) {
            $integer  = (int) $integerRaw;
            $fraction = (int) $fractionRaw;

            // The total number of digits (precision) must not exceed 65.
            if (($integer + $fraction) > self::DECIMAL_MAX_PRECISION) {
                $errors['optionsIntegerDigitsLength'] = $this->l->t(
                    'The sum of integer and fraction digits must not exceed %s.',
                    [self::DECIMAL_MAX_PRECISION]
                );
                $errors['optionsFractionDigitsLength'] = $this->l->t(
                    'The sum of integer and fraction digits must not exceed %s.',
                    [self::DECIMAL_MAX_PRECISION]
                );
            }

            // At least 1 digit required in total.
            if (($integer + $fraction) < 1) {
                $errors['optionsIntegerDigitsLength'] = $this->l->t('At least 1 digit must be defined in total.');
            }
        }

        return $errors;
    }

    private function validateForeignKeyOptions(array $data, array $errors): array
    {
        $options = $data['options']['foreignKey'] ?? null;
        $targetEntity = $options['targetEntity'] ?? '';

        if (trim($targetEntity) === '') {
            $errors['optionsTargetEntity'] = $this->l->t('A target entity must be selected.');
            return $errors;
        }

        if (!ForeignKeyRegistry::isValidTarget($targetEntity)) {
            $errors['optionsTargetEntity'] = $this->l->t('This target entity is not supported.');
            return $errors;
        }

        $composition = $options['labelComposition'] ?? null;

        if (!is_array($composition) || count($composition) === 0) {
            $errors['optionsLabelComposition'] = $this->l->t('A label composition must be defined.');
            return $errors;
        }

        $validFieldIds = ForeignKeyRegistry::getValidLabelFieldIds($targetEntity);
        $hasFieldEntry = false;

        foreach ($composition as $item) {
            if (!is_array($item) || !isset($item['type'])) {
                $errors['optionsLabelComposition'] = $this->l->t('The label composition is malformed.');
                return $errors;
            }

            if ($item['type'] === 'field') {
                $fieldId = $item['id'] ?? null;

                if (!is_string($fieldId) || !in_array($fieldId, $validFieldIds, true)) {
                    $errors['optionsLabelComposition'] = $this->l->t('One or more selected fields are invalid.');
                    return $errors;
                }

                $hasFieldEntry = true;
            } elseif ($item['type'] === 'text') {
                if (!is_string($item['value'] ?? null)) {
                    $errors['optionsLabelComposition'] = $this->l->t('Fixed text entries must be text.');
                    return $errors;
                }
            } else {
                $errors['optionsLabelComposition'] = $this->l->t('The label composition contains an unknown entry type.');
                return $errors;
            }
        }

        if (!$hasFieldEntry) {
            $errors['optionsLabelComposition'] = $this->l->t('The label composition must contain at least one field.');
        }

        return $errors;
    }

    private function validateMaxLengthOptions(array $data, array $errors, string $type, int $min, int $max): array
    {
        $options = $data['options'][$type] ?? null;

        if (!is_array($options)) {
            $errors['optionsMaxLength'] = $this->l->t(
                'A number between %s and %s must be defined.',
                [$min, $max]
            );

            return $errors;
        }

        $maxRaw = $options['maxLength'] ?? '';

        if (!$this->isValidDigitLength($maxRaw, $min, $max)) {
            $errors['optionsMaxLength'] = $this->l->t(
                'Max length must be a whole number between %s and %s.',
                [$min, $max]
            );
        }

        return $errors;
    }

    private function validateMinLengthValidation(array $data, array $errors, string $type, int $min, int $max): array
    {
        $validation = $data['validation'][$type] ?? null;

        if (!is_array($validation) || empty($validation['enabled'])) {
            return $errors;
        }

        $optionsMaxLength = (int)($data['options'][$type]['maxLength'] ?? $max);
        $minRaw = $validation['minLength'] ?? '';

        if (!$this->isValidDigitLength($minRaw, $min, $optionsMaxLength)) {
            $errors['validationMinLength'] = $this->l->t(
                'Min length must be a whole number between %s and %s.',
                [$min, $optionsMaxLength]
            );
        }

        return $errors;
    }

    public function validateUpdate(array $data, ?int $excludeId = null): array
    {
        $errors = $this->validateName($data, $excludeId, []);

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    private function validateName(array $data, ?int $excluedId = null, $errors = []) : array
    {
        // a) name: at least 1 signs.
        if (empty($data['name'])) {
            $errors['name'] = $this->l->t('The field "name" is required.');
        } elseif (mb_strlen(trim($data['name'])) < 1) {
            $errors['name'] = $this->l->t('The name must be at least 1 character long.');
        } else {
            // b) name: must be unique in group.
            $existing = $this->customFieldMapper->findByNameAndCustomFieldGroupId(trim($data['name']), intval($data['customFieldGroupId']));

            if ($existing !== null && $existing->getId() !== $excludeId) {
                $errors['name'] = $this->l->t('A custom field with this name already exists in this custom field group.');
            }
        }

        return $errors;
    }
}