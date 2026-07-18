<?php declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCA\SfxonItam\Db\CustomFieldGroupMapper;
use OCA\SfxonItam\Db\CustomFieldMapper;
use OCP\IL10N;

class CustomFieldValidator {
    private const DECIMAL_MAX_PRECISION = 65;
    private const DECIMAL_MAX_SCALE = 30;
    private const TEXT_MIN_LENGTH = 1;
    private const TEXT_MAX_LENGTH = 4096;
    

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
            $type !== 'text' &&
            $type !== 'integer' &&
            $type !== 'decimal' &&
            $type !== 'boolean' &&
            $type !== 'file' &&
            $type !== 'longtext' &&
            $type !== 'date'
        ) {
            $errors['type'] = $this->l->t('This type is not supported.');
        }

        // g) Check text.
        if ($type === 'text') {
            $errors = $this->validateTextOptions($data, $errors);
            $errors = $this->validateTextValidation($data, $errors);
        }

        // h) Check decimal options
        if ($type === 'decimal') {
            $errors = $this->validateDecimalOptions($data, $errors);
        }

        // i) Validate long text.
        if ($type === 'longtext') {
            $errors = $this->validateLongtext($data, $errors);
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

    private function validateLongtext(array $data, array $errors): array
    {
        $validation = $data['validation']['longtext'] ?? null;

        if (!is_array($validation) || empty($validation['enabled'])) {
            return $errors;
        }

        $minRaw = $validation['minLength'] ?? '';
        $minValid = (int) $minRaw > 0;

        if (!$minValid) {
            $errors['validationMinLength'] = $this->l->t(
                'Min length must be a whole positive number.'
            );
        }

        return $errors;
    }

    private function validateTextOptions(array $data, array $errors): array
    {
        $options = $data['options']['text'] ?? null;

        if (!is_array($options)) {
            $errors['optionsMaxLength'] = $this->l->t(
                'A number between %s and %s must be defined.',
                [self::TEXT_MIN_LENGTH, self::TEXT_MAX_LENGTH]
            );

            return $errors;
        }

        $maxRaw = $options['maxLength'] ?? '';
        $maxValid = $this->isValidDigitLength($maxRaw, self::TEXT_MIN_LENGTH, self::TEXT_MAX_LENGTH);

        if (!$maxValid) {
            $errors['optionsMaxLength'] = $this->l->t(
                'Max length must be a whole number between %s and %s.',
                [self::TEXT_MIN_LENGTH, self::TEXT_MAX_LENGTH]
            );
        }

        return $errors;
    }

    private function validateTextValidation(array $data, array $errors): array
    {
        $validation = $data['validation']['text'] ?? null;

        if (!is_array($validation) || empty($validation['enabled'])) {
            return $errors;
        }

        $optionsMaxLength = (int)($data['options']['text']['maxLength'] ?? self::TEXT_MAX_LENGTH);
        $minRaw = $validation['minLength'] ?? '';
        $minValid = $this->isValidDigitLength($minRaw, self::TEXT_MIN_LENGTH, $optionsMaxLength);

        if (!$minValid) {
            $errors['validationMinLength'] = $this->l->t(
                'Min length must be a whole number between %s and %s.',
                [self::TEXT_MIN_LENGTH, $optionsMaxLength]
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