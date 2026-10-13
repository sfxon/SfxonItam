<?php declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\DeviceStatusMapper;
use OCP\IL10N;

class DeviceValidator {
    public function __construct(
        private DeviceMapper $mapper,
        private DeviceStatusMapper $deviceStatusMapper,
        private IL10N $l,
    )
    {
    }

    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // a) name: at least 3 signs.
        if (empty($data['name'])) {
            $errors['name'] = $this->l->t('The field "name" is required.');
        } elseif (mb_strlen(trim($data['name'])) < 3) {
            $errors['name'] = $this->l->t('The name must be at least 3 characters long.');
        } else {
            // b) name: must be unique.
            $existing = $this->mapper->findByName(trim($data['name']));
            if ($existing !== null && $existing->getId() !== $excludeId) {
                $errors['name'] = $this->l->t('A device with this name already exists.');
            }
        }

        // c) purchaseDate: optional, but if given it must be a valid date that is not in the future.
        $purchaseDate = $data['purchaseDate'] ?? null;

        if ($purchaseDate === '') {
            $errors['purchaseDate'] = $this->l->t('The field purchaseDate is required.');
        } elseif ($purchaseDate !== null) {
            $date = is_string($purchaseDate) ? \DateTimeImmutable::createFromFormat('Y-m-d', $purchaseDate) : false;
            $isValidFormat = $date && $date->format('Y-m-d') === $purchaseDate;

            if (!$isValidFormat) {
                $errors['purchaseDate'] = $this->l->t('The date must be in the format YYYY-MM-DD.');
            } elseif ($date > new \DateTimeImmutable('today')) {
                $errors['purchaseDate'] = $this->l->t('The invoice date must not be in the future.');
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}