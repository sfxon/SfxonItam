<?php declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCP\IL10N;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IUserSession;
use OCA\SfxonItam\Service\CustomFieldEntityRegistryService;

class CustomFieldDataValidator {
    public function __construct(
        private readonly CustomFieldEntityRegistryService $customFieldEntityRegistryService,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession,
        private readonly IL10N $l,)
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

    public function validateDate(string $fieldName, mixed $validation, mixed $value): array
    {
        $errors = [];

        if (
            $validation === null ||
            !isset($validation['date']) ||
            $validation['date']['enabled'] === false
        ) {
            return [];
        }

        $rules = $validation['date'];
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

        if (!is_string($value)) {
            $errors['customFields.' . $fieldName] = $this->l->t('Invalid date.', [$fieldName]);
            return $errors;
        }

        $format = 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);

        // CreateFromFormat returns false on format errors.
        // Additionally check the output of getLastErrors(), 
        // because it checks for valid date (e.g. it does not allow 2023-02-30).
        $errorsInfo = \DateTime::getLastErrors();
        $dateHasErrors = false;
        
        if($errorsInfo !== false && ($errorsInfo['warning_count'] > 0 || $errorsInfo['error_count'] > 0)) {
            $dateHasErrors = true;
        }

        if (
            $date === false ||
            $dateHasErrors ||
            $date->format($format) !== $value
        ) {
            $errors['customFields.' . $fieldName] = $this->l->t('A valid date must be selected.', [$fieldName]);
        }

        return $errors;
    }

    public function validateDatetime(string $fieldName, mixed $validation, mixed $value): array
    {
        $errors = [];

        if (
            $validation === null ||
            !isset($validation['datetime']) ||
            $validation['datetime']['enabled'] === false
        ) {
            return [];
        }

        $rules = $validation['datetime'];
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

        if (!is_string($value)) {
            $errors['customFields.' . $fieldName] = $this->l->t('Invalid date/time.', [$fieldName]);
            return $errors;
        }

        $format = 'Y-m-d H:i:s';
        $dateTime = \DateTime::createFromFormat($format, $value);

        // CreateFromFormat returns false on format errors.
        // Additionally check the output of getLastErrors(), 
        // because it checks for valid date (e.g. it does not allow 2023-02-30).
        $errorsInfo = \DateTime::getLastErrors();
        $datetimeHasErrors = false;
        
        if($errorsInfo !== false && ($errorsInfo['warning_count'] > 0 || $errorsInfo['error_count'] > 0)) {
            $datetimeHasErrors = true;
        }

        if (
            $dateTime === false ||
            $datetimeHasErrors ||
            $dateTime->format($format) !== $value
        ) {
            $errors['customFields.' . $fieldName] = $this->l->t('A valid date and time must be selected.', [$fieldName]);
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

    public function validateFile(string $fieldName, mixed $options, mixed $validation, mixed $value): array
    {
        $errors = [];

        if (
            $validation === null ||
            !isset($validation['file']) ||
            $validation['file']['enabled'] === false
        ) {
            return [];
        }

        $rules = $validation['file'];
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

        // Value must be a valid Nextcloud file id.
        $fileId = filter_var($value, FILTER_VALIDATE_INT);

        if ($fileId === false) {
            $errors['customFields.' . $fieldName] = $this->l->t('The selected file is invalid.', [$fieldName]);
            return $errors;
        }

        $user = $this->userSession->getUser();

        if ($user === null) {
            // This should never happen, since this module can only be used by logged in users.
            // But if at any time someone tries to use this method in a different context, this check is kept.
            throw new \Exception('User must be logged in to select a file.');
        }

        try {
            $userFolder = $this->rootFolder->getUserFolder($user->getUID());
            $nodes = $userFolder->getById($fileId);
        } catch (NotPermittedException) {
            $nodes = [];
        }

        if (empty($nodes)) {
            $errors['customFields.' . $fieldName] = $this->l->t('The selected file is no longer available. It may have been changed or removed by someone else in the meantime. Please reload the page and select again.');
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

    public function validateText(string $fieldName, mixed $validation, mixed $value): array
    {
        $errors = [];

        if (
            $validation === null ||
            !isset($validation['text']) ||
            $validation['text']['enabled'] === false
        ) {
            return [];
        }

        $rules = $validation['text'];

        if(isset($rules['minLength']) && is_numeric($rules['minLength'])) {
            $minLength = (int)$rules['minLength'];

            if (empty($value) || mb_strlen(trim($value)) < $minLength) {
                $errors['customFields.' . $fieldName] = $this->l->t('You should enter at least %d signs.', [$minLength]);
            }
        }

        return $errors;
    }
}