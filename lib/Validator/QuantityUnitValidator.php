<?php

declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCP\IL10N;

class QuantityUnitValidator {
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
        } elseif (mb_strlen(trim($data['name'])) < 1) {
            $errors['name'] = $this->l->t('The name must be at least 1 character long.');
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}