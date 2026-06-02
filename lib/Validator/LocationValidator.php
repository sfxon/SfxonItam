<?php declare(strict_types=1);

namespace OCA\SfxonItam\Validator;

use OCA\SfxonItam\Db\LocationMapper;
use OCP\IL10N;

class LocationValidator {
    public function __construct(
        private IL10N $l,
        private LocationMapper $mapper,)
    {
    }

    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // a) name: at least 1 signs.
        if (empty($data['name'])) {
            $errors['name'] = $this->l->t('The field "name" is required.');
        } elseif (mb_strlen(trim($data['name'])) < 1) {
            $errors['name'] = $this->l->t('The name must be at least 1 character long.');
        } else {
            // b) name: must be unique.
            $existing = $this->mapper->findByName(trim($data['name']));

            if ($existing !== null && $existing->getId() !== $excludeId) {
                $errors['name'] = $this->l->t('A location with this name already exists.');
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}