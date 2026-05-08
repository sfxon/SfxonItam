<?php

declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCP\IL10N;

class ItamUserValidator {
    public function __construct(
        private IL10N $l,
    )
    {
    }

    public function validate(array $data): array {
        $errors = [];

        // a) firstname: mindestens 1 Zeichen
        if (empty($data['firstname'])) {
            $errors['firstname'] = $this->l->t('The field "firstname" is required.');
        } elseif (mb_strlen(trim($data['firstname'])) < 1) {
            $errors['firstname'] = $this->l->t('The firstname must be at least 1 character long.');
        }

        // b) lastname: mindestens 1 Zeichen
        if (empty($data['lastname'])) {
            $errors['lastname'] = $this->l->t('The field "lastname" is required.');
        } elseif (mb_strlen(trim($data['lastname'])) < 1) {
            $errors['lastname'] = $this->l->t('The lastname must be at least 1 character long.');
        }

        // c) email: mindestens 1 Zeichen
        if (empty($data['email'])) {
            $errors['email'] = $this->l->t('The field "email" is required.');
        } elseif (mb_strlen(trim($data['email'])) < 1) {
            $errors['email'] = $this->l->t('The email must be at least 1 character long.');
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}