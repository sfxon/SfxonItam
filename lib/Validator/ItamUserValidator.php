<?php declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCA\SfxonItam\Db\ItamUserMapper;
use OCP\IL10N;

class ItamUserValidator {
    public function __construct(
        private IL10N $l,
        private ItamUserMapper $mapper,)
    {
    }

    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // a) firstname: at least 1 signs.
        if (empty($data['firstname'])) {
            $errors['firstname'] = $this->l->t('The field "firstname" is required.');
        } elseif (mb_strlen(trim($data['firstname'])) < 1) {
            $errors['firstname'] = $this->l->t('The firstname must be at least 1 character long.');
        }

        // b) lastname: at least 1 signs.
        if (empty($data['lastname'])) {
            $errors['lastname'] = $this->l->t('The field "lastname" is required.');
        } elseif (mb_strlen(trim($data['lastname'])) < 1) {
            $errors['lastname'] = $this->l->t('The lastname must be at least 1 character long.');
        }

        // c) email: at least 5 signs (absolutely minimum check.)
        if (empty($data['email'])) {
            $errors['email'] = $this->l->t('The field "email" is required.');
        } elseif (mb_strlen(trim($data['email'])) < 5) {
            $errors['email'] = $this->l->t('The email must be at least 5 characters long.');
        } else {
            // d) email: must be unique.
            $existing = $this->mapper->findByEmail(trim($data['email']));

            if ($existing !== null && $existing->getId() !== $excludeId) {
                $errors['email'] = $this->l->t('A user with this email address already exists.');
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}
