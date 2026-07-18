<?php declare(strict_types=1);

namespace OCA\SfxonItam\ForeignKey;

/**
 * Hardcoded registry of entities that can be referenced by a
 * "foreign_key" custom field. Extend this list manually whenever
 * a new entity should become referenceable.
 */
class ForeignKeyRegistry {
    /**
     * @return array<string, array{label: string, table: string, labelFields: array<int, array{id: string, label: string}>}>
     */
    public static function getTargets(): array {
        return [
            'device' => [
                'label' => 'Device',
                'table' => 'sfxon_device',
                'labelFields' => [
                    ['id' => 'name', 'label' => 'Name'],
                    ['id' => 'serialNumber', 'label' => 'Serial Number'],
                ],
            ],
            'user' => [
                'label' => 'Bento User',
                'table' => 'sfxon_itam_user',
                'labelFields' => [
                    ['id' => 'firstname', 'label' => 'Firstname'],
                    ['id' => 'lastname', 'label' => 'Lastname'],
                    ['id' => 'email', 'label' => 'Email'],
                ],
            ],
            // Add further entities here as needed.
        ];
    }

    public static function isValidTarget(string $key): bool {
        return array_key_exists($key, self::getTargets());
    }

    public static function getTarget(string $key): ?array {
        return self::getTargets()[$key] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function getValidLabelFieldIds(string $targetKey): array {
        $target = self::getTarget($targetKey);

        if ($target === null) {
            return [];
        }

        return array_map(fn ($f) => $f['id'], $target['labelFields']);
    }
}