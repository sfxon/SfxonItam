<?php

declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCA\SfxonItam\Db\DeviceStatusMapper;
use OCP\IL10N;

class DeviceValidator {
    public function __construct(
        private DeviceStatusMapper $deviceStatusMapper,
        private IL10N $l,
    )
    {
    }

    public function validate(array $data): array {
        $errors = [];

        // name: mindestens 3 Zeichen
        if (empty($data['name'])) {
            $errors['name'] = $this->l->t('The field "name" is required.');
        } elseif (mb_strlen(trim($data['name'])) < 3) {
            $errors['name'] = $this->l->t('The name must be at least 3 characters long.');
        }

        // deviceStatusId: Must be a valid DeviceStatus Entry from the database or null.
        if (empty($data['deviceStatusId']) && null !== $data['deviceStatusId'] ) {
            $errors['deviceStatusId'] = $this->l->t('The field deviceStatusId is required.');
        } else if(null !== $data['deviceStatusId']) {
            try {
                $this->deviceStatusMapper->findById($data['deviceStatusId']);
            } catch(\Exception $e) {
                $errors['deviceStatusId'] = $this->l->t('Invalid deviceStatusId.');
            }
        }

        // purchaseDate
        if (empty($data['purchaseDate']) && null !== $data['purchaseDate'] ) {
            $errors['purchaseDate'] = $this->l->t('The field purchaseDate is required.');
        } else if(null !== $data['purchaseDate']) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $data['purchaseDate']);
            $isValidFormat = $date && $date->format('Y-m-d') === $data['purchaseDate'];

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