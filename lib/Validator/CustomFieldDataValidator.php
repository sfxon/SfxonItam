<?php declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCP\IL10N;
use OCA\SfxonItam\Service\CustomFieldEntityRegistryService;

class CustomFieldDataValidator {
    public function __construct(
        private readonly CustomFieldEntityRegistryService $customFieldEntityRegistryService,
        private IL10N $l,)
    {
    }

    public function validateBoolean(string $fieldName, mixed $validation, mixed $value): array
    {
        $errors = [];

        if (
            $validation === null ||
            !isset($validation['boolean']) ||
            $validation['boolean']['enabled'] === false
        ) {
            return [];
        }

        $rules = $validation['boolean'];
        $required = $rules['required'] ?? false;

        if (is_string($value)) {
            $value = trim($value);
        }

        // Not required: null value is allowed.
        if ($value === null) {
            if ($required) {
                $errors['customFields.' . $fieldName] = $this->l->t('This field is required.', [$fieldName]);
            }
        }

        return $errors;
    }

    public function validateDecimal(string $fieldName, mixed $options, mixed $validation, mixed $value): array
    {
        $errors = [];

        if (
            $validation === null ||
            !isset($validation['decimal']) ||
            $validation['decimal']['enabled'] === false
        ) {
            return [];
        }

        $rules = $validation['decimal'];
        $required = $rules['required'] ?? false;

        if (is_string($value)) {
            $value = trim($value);
        }

        // Not required: null/empty value is allowed.
        if ($value === null || $value === '') {
            if ($required) {
                $errors['customFields.' . $fieldName] = $this->l->t('This field is required.', [$fieldName]);
            }

            return $errors;
        }

        $rawValue = (string)$value;

        // Only a single decimal separator allowed — either "." or ",", not both,
        // and not more than one of either. This rejects thousands-separator
        // formats like "1.234,56" or "1,234,567.89" which are ambiguous.
        $separatorCount = substr_count($rawValue, '.') + substr_count($rawValue, ',');

        if ($separatorCount > 1) {
            $errors['customFields.' . $fieldName] = $this->l->t('Only one decimal separator ("." or ",") is allowed.', [$fieldName]);

            return $errors;
        }

        // Normalize comma to dot for validation and further processing.
        $normalizedValue = str_replace(',', '.', $rawValue);

        // Value must be a valid decimal number.
        // Accepts: "55", "55.0", "55.", ".55" — but not "." alone.
        $isValidDecimal = is_numeric($normalizedValue) && (bool)preg_match('/^-?(\d+(\.\d*)?|\.\d+)$/', $normalizedValue);

        if (!$isValidDecimal) {
            $errors['customFields.' . $fieldName] = $this->l->t('Must be a valid decimal number.', [$fieldName]);

            return $errors;
        }

        // Digit-length constraints from $options, if configured.
        $decimalOptions = $options['decimal'] ?? [];

        $integerDigitsLength = isset($decimalOptions['integerDigitsLength']) && is_numeric($decimalOptions['integerDigitsLength'])
            ? (int)$decimalOptions['integerDigitsLength']
            : null;

        $fractionDigitsLength = isset($decimalOptions['fractionDigitsLength']) && is_numeric($decimalOptions['fractionDigitsLength'])
            ? (int)$decimalOptions['fractionDigitsLength']
            : null;

        $stringValue = ltrim($normalizedValue, '-');
        [$integerPart, $fractionPart] = array_pad(explode('.', $stringValue, 2), 2, '');

        if ($integerDigitsLength !== null && mb_strlen($integerPart) > $integerDigitsLength) {
            $errors['customFields.' . $fieldName] = $this->l->t('The integer part must not exceed %d digits.', [$integerDigitsLength]);
        }

        if ($fractionDigitsLength !== null && mb_strlen($fractionPart) > $fractionDigitsLength) {
            $errors['customFields.' . $fieldName] = $this->l->t('The fraction part must not exceed %d digits.', [$fractionDigitsLength]);
        }

        return $errors;
    }

    public function validateForeignKey(string $fieldName, mixed $options, mixed $validation, mixed $value): array
    {
        $errors = [];

        if (
            $validation === null ||
            !isset($validation['foreignKey']) ||
            $validation['foreignKey']['enabled'] === false
        ) {
            return [];
        }

        $rules = $validation['foreignKey'];
        $required = $rules['required'] ?? false;

        if (is_string($value)) {
            $value = trim($value);
        }

        // Not required: null/empty value is allowed.
        if ($required && ($value === null || $value === '')) {
            $errors['customFields.' . $fieldName] = $this->l->t('This field is required.', [$fieldName]);
            return $errors;
        }

        // If validation is enabled: Entry must be an entry in the foreign table.
        $entityName = $options['foreignKey']['targetEntity'];
        $result = $this->customFieldEntityRegistryService->findById($entityName, $value);

        if($result === null) {
            $errors['customFields.' . $fieldName] = $this->l->t('The selected entry is no longer available. It may have been changed or removed by someone else in the meantime. Please reload the page and select again.');
        }

        return $errors;
    }

    public function validateInteger(string $fieldName, mixed $validation, mixed $value): array
    {
        $errors = [];

        if (
            $validation === null ||
            !isset($validation['integer']) ||
            $validation['integer']['enabled'] === false
        ) {
            return [];
        }

        $rules = $validation['integer'];
        $required = $rules['required'] ?? false;

        if (is_string($value)) {
            $value = trim($value);
        }

        // Not required: null/empty value is allowed.
        if ($value === null || $value === '') {
            if ($required) {
                $errors['customFields.' . $fieldName] = $this->l->t('This field is required.', [$fieldName]);
            }

            return $errors;
        }

        // Required: value must be 0 or a valid number.
        $isValidInteger = is_int($value) || (is_string($value) && $value !== '-' && ctype_digit(ltrim($value, '-')));

        if (!$isValidInteger) {
            $errors['customFields.' . $fieldName] = $this->l->t('Must be a valid integer.', [$fieldName]);
        }

        return $errors;
    }

    public function validateLongtext(string $fieldName, mixed $validation, mixed $value): array
    {
        $errors = [];

        if (
            $validation === null ||
            !isset($validation['longtext']) ||
            $validation['longtext']['enabled'] === false
        ) {
            return [];
        }

        $rules = $validation['longtext'];

        if(isset($rules['minLength']) && is_numeric($rules['minLength'])) {
            $minLength = (int)$rules['minLength'];

            if (empty($value) || mb_strlen(trim($value)) < $minLength) {
                $errors['customFields.' . $fieldName] = $this->l->t('You should enter at least %d signs.', [$minLength]);
            }
        }

        return $errors;
    }

    /*
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
    */
}