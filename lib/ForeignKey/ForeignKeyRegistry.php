<?php declare(strict_types=1);

namespace OCA\SfxonItam\ForeignKey;

use OCA\SfxonItam\Definition\EntityDefinition;
use OCA\SfxonItam\Definition\EntityRegistry;

/**
 * Registry of everything that can be referenced by a "foreign_key" custom field.
 *
 * Every EntityDefinition is available automatically. Additional tables without an EntityDefinition
 * can be added in getManualTargets().
 */
class ForeignKeyRegistry {
    private const HIDDEN_FIELD_PATTERN = '/password|passwd|secret|token|hash/i';

    private static ?EntityRegistry $entityRegistry = null;

    /** @var array<string, array|null> */
    private static array $resolved = [];

    /**
     * Targets, that are not backed by an EntityDefinition. Same key as an entity overrides it.
     * Label fields may define "column" (default: snake_case of the id) and "text" (default: true).
     *
     * @return array<string, array{label: string, table: string, route?: string, labelFields: array<int, array{id: string, label: string, column?: string, text?: bool}>}>
     */
    private static function getManualTargets(): array {
        return [];
    }

    /**
     * @return array<string, array{label: string, table: string, entity: ?string, route: ?string, labelFields: array<int, array{id: string, label: string}>, columns: array<string, array{column: string, text: bool}>}>
     */
    public static function getTargets(): array {
        $targets = [];

        foreach (self::entityRegistry()->all() as $key => $definition) {
            $targets[$key] = self::getTarget($key);
        }

        foreach (array_keys(self::getManualTargets()) as $key) {
            $targets[$key] = self::getTarget($key);
        }

        return array_filter($targets);
    }

    public static function isValidTarget(string $key): bool {
        return self::getTarget($key) !== null;
    }

    public static function getTarget(string $key): ?array {
        if (array_key_exists($key, self::$resolved)) {
            return self::$resolved[$key];
        }

        $manual = self::getManualTargets()[$key] ?? null;

        if ($manual !== null) {
            $target = self::describeManual($manual);
        } elseif (self::entityRegistry()->has($key)) {
            $target = self::describeEntity(self::entityRegistry()->get($key));
        } else {
            $target = null;
        }

        return self::$resolved[$key] = $target;
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

    private static function entityRegistry(): EntityRegistry {
        return self::$entityRegistry ??= new EntityRegistry();
    }

    private static function describeEntity(EntityDefinition $definition): array {
        $labelFields = [];
        $columns = [];

        if (method_exists($definition->entityClass, 'getFieldDefinition')) {
            foreach ($definition->entityClass::getFieldDefinition() as $field) {
                $id = (string)($field['propertyName'] ?? $field['name']);
                $column = (string)$field['name'];

                if ($id === 'id'
                    || !empty($field['foreignEntity'])
                    || preg_match('/^[A-Za-z0-9_]+$/', $column) !== 1
                    || preg_match(self::HIDDEN_FIELD_PATTERN, $id . ' ' . $column) === 1) {
                    continue;
                }

                $labelFields[] = ['id' => $id, 'label' => self::humanize($id)];
                $columns[$id] = [
                    'column' => $column,
                    'text' => in_array(strtoupper((string)($field['type'] ?? '')), ['VARCHAR', 'TEXT'], true),
                ];
            }
        }

        return [
            'label' => $definition->label,
            'table' => $definition->customFieldGroup,
            'entity' => $definition->key,
            'route' => $definition->route,
            'labelFields' => $labelFields,
            'columns' => $columns,
        ];
    }

    private static function describeManual(array $target): array {
        $labelFields = [];
        $columns = [];

        foreach ($target['labelFields'] as $field) {
            $id = (string)$field['id'];

            $labelFields[] = ['id' => $id, 'label' => (string)$field['label']];
            $columns[$id] = [
                'column' => (string)($field['column'] ?? strtolower((string)preg_replace('/[A-Z]/', '_$0', $id))),
                'text' => (bool)($field['text'] ?? true),
            ];
        }

        return [
            'label' => $target['label'],
            'table' => $target['table'],
            'entity' => null,
            'route' => $target['route'] ?? null,
            'labelFields' => $labelFields,
            'columns' => $columns,
        ];
    }

    private static function humanize(string $id): string {
        return ucfirst(trim((string)preg_replace('/(?<!^)[A-Z]/', ' $0', $id)));
    }
}