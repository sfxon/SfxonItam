<?php declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCA\SfxonItam\Db\Device;
use OCA\SfxonItam\Db\DeviceMapper;
use OCP\IL10N;

class DeviceValidator {
    private const NAME_MIN_LENGTH = 3;

    public function __construct(
        private DeviceMapper $mapper,
        private IL10N $l,
    )
    {
    }

    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        $isCreate = $excludeId === null;

	// Generic validations for a couple of defined fields.
        foreach (Device::getFieldDefinition() as $def) {
            $prop = $def['propertyName'];

            // id wird nicht vom Client geliefert, purchaseDate wird unten speziell gepr�ft.
            if ($prop === 'id' || $prop === 'purchaseDate') {
                continue;
            }

            $value = $data[$prop] ?? null;
            $isEmpty = $value === null || (is_string($value) && trim($value) === '');
            $required = $isCreate ? $def['requiredOnCreate'] : $def['requiredOnUpdate'];

            if ($isEmpty) {
                if ($required) {
                    $errors[$prop] = $this->l->t('The field "%s" is required.', [$prop]);
                }
                continue;
            }

            switch ($def['type']) {
                case 'VARCHAR':
                    $maxLength = (int)$def['length'];
                    if (!is_string($value)) {
                        $errors[$prop] = $this->l->t('The field "%s" must be a string.', [$prop]);
                    } elseif (mb_strlen(trim($value)) > $maxLength) {
                        $errors[$prop] = $this->l->t(
                            'The field "%1$s" must not be longer than %2$d characters.',
                            [$prop, $maxLength]
                        );
                    }
                    break;

                case 'TEXT':
                    if (!is_string($value)) {
                        $errors[$prop] = $this->l->t('The field "%s" must be a string.', [$prop]);
                    }
                    break;

                case 'BIGINT':
                    $isValidId = (is_int($value) && $value > 0)
                        || (is_string($value) && ctype_digit($value) && (int)$value > 0);
                    if (!$isValidId) {
                        $errors[$prop] = $this->l->t('The field "%s" must be a valid ID.', [$prop]);
                    }
                    break;

                case 'DECIMAL':
                    [$precision, $scale] = array_map('intval', explode(',', (string)$def['length']));
                    if (!is_numeric($value)) {
                        $errors[$prop] = $this->l->t('The field "%s" must be a number.', [$prop]);
                    } elseif (abs((float)$value) >= 10 ** ($precision - $scale)) {
                        $errors[$prop] = $this->l->t('The value of "%s" is too large.', [$prop]);
                    }
                    break;
            }
        }

        // 2) name: Min-length and uniqueness.
        if (!isset($errors['name'])) {
            $name = trim((string)($data['name'] ?? ''));
            if ($name !== '') {
                if (mb_strlen($name) < self::NAME_MIN_LENGTH) {
                    $errors['name'] = $this->l->t(
                        'The name must be at least %d characters long.',
                        [self::NAME_MIN_LENGTH]
                    );
                } else {
                    $existing = $this->mapper->findByName($name);
                    if ($existing !== null && $existing->getId() !== $excludeId) {
                        $errors['name'] = $this->l->t('A device with this name already exists.');
                    }
                }
            }
        }

        // 3) purchaseDate: optional, format YYYY-MM-DD, not in the future.
        $purchaseDate = $data['purchaseDate'] ?? null;
        if ($purchaseDate !== null && $purchaseDate !== '') {
            // "!" sets time to 00:00:00, otherwise the current date would be invalid.
            $date = is_string($purchaseDate)
                ? \DateTimeImmutable::createFromFormat('!Y-m-d', $purchaseDate)
                : false;

            if (!$date || $date->format('Y-m-d') !== $purchaseDate) {
                $errors['purchaseDate'] = $this->l->t('The date must be in the format YYYY-MM-DD.');
            } elseif ($date > new \DateTimeImmutable('today')) {
                $errors['purchaseDate'] = $this->l->t('The purchase date must not be in the future.');
            }
        }

        return [
            'valid'  => $errors === [],
            'errors' => $errors,
        ];
    }
}