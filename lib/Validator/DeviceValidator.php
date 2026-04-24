<?php

declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCP\IL10N;

class DeviceValidator {
    public function __construct(
        private IL10N $l,
    )
    {
    }

    public function validate(array $data): array {
        $errors = [];

        // a) name: mindestens 3 Zeichen
        if (empty($data['name'])) {
            $errors['name'] = $this->l->t('The field "name" is required.');
        } elseif (mb_strlen(trim($data['name'])) < 3) {
            $errors['name'] = $this->l->t('The name must be at least 3 characters long.');
        }

        // b) purchaseDate
        if (empty($data['invoiceDate']) && null !== $data['invoiceDate'] ) {
            $errors['invoiceDate'] = $this->l->t('The field invoiceDate is required.');
        } else if(null !== $data['invoiceDate']) {
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