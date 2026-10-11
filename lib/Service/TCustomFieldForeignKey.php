<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\Definition\EntityRegistry;
use OCP\DB\QueryBuilder\IQueryBuilder;

trait TCustomFieldForeignKey
{
    /**
     * @return array{table: string, parts: list<array{text?: string, column?: string}>, columns: array<string, bool>}|null
     */
    public function describeForeignKey(mixed $customField, EntityRegistry $entityRegistry): ?array
    {
        $options = $customField['options'] ?? null;

        if (is_string($options)) {
            $options = json_decode($options, true);
        }

        $foreignKey = is_array($options) ? ($options['foreignKey'] ?? null) : null;

        if (!is_array($foreignKey)) {
            return null;
        }

        $targetEntity = $foreignKey['targetEntity'] ?? null;

        if (!is_string($targetEntity) || !$entityRegistry->has($targetEntity)) {
            return null;
        }

        $target = $entityRegistry->get($targetEntity);

        if (!method_exists($target->entityClass, 'getFieldDefinition')
            || preg_match('/^[A-Za-z0-9_]+$/', $target->customFieldGroup) !== 1) {
            return null;
        }

        $fieldsByProperty = [];

        foreach ($target->entityClass::getFieldDefinition() as $field) {
            $fieldsByProperty[$field['propertyName'] ?? $field['name']] = $field;
        }

        $parts = [];
        $columns = [];

        foreach ($foreignKey['labelComposition'] ?? [] as $part) {
            if (!is_array($part)) {
                continue;
            }

            if (($part['type'] ?? null) === 'text') {
                $parts[] = ['text' => (string)($part['value'] ?? '')];
                continue;
            }

            if (($part['type'] ?? null) !== 'field') {
                continue;
            }

            $field = $fieldsByProperty[$part['id'] ?? ''] ?? null;

            if ($field === null || preg_match('/^[A-Za-z0-9_]+$/', (string)$field['name']) !== 1) {
                continue;
            }

            $parts[] = ['column' => $field['name']];
            $columns[$field['name']] = in_array(strtoupper((string)($field['type'] ?? '')), ['VARCHAR', 'TEXT'], true);
        }

        if ($columns === []) {
            return null;
        }

        return ['table' => $target->customFieldGroup, 'parts' => $parts, 'columns' => $columns];
    }

    /**
     * Adds "customFieldLabels" (technicalName => composed label) to every row, using one query per target table.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public function addForeignKeyLabels(array $customFieldDefinitions, array $rows, EntityRegistry $entityRegistry): array
    {
        $targets = [];

        foreach ($customFieldDefinitions as $definition) {
            $technicalName = $definition['technicalName'] ?? null;

            if (($definition['type'] ?? null) !== 'foreign_key' || !is_string($technicalName)) {
                continue;
            }

            $description = $this->describeForeignKey($definition, $entityRegistry);

            if ($description !== null) {
                $targets[$technicalName] = $description;
            }
        }

        if ($targets === []) {
            return $rows;
        }

        $idsByTable = [];
        $columnsByTable = [];

        foreach ($rows as $row) {
            $values = is_array($row['customFields'] ?? null) ? $row['customFields'] : [];

            foreach ($targets as $technicalName => $target) {
                $id = $values[$technicalName] ?? null;

                if (!is_numeric($id) || (int)$id <= 0) {
                    continue;
                }

                $idsByTable[$target['table']][(int)$id] = true;

                foreach (array_keys($target['columns']) as $column) {
                    $columnsByTable[$target['table']][$column] = true;
                }
            }
        }

        if ($idsByTable === []) {
            return $rows;
        }

        $recordsByTable = [];

        foreach ($idsByTable as $table => $ids) {
            $qb = $this->db->getQueryBuilder();
            $qb->select(array_merge(['id'], array_keys($columnsByTable[$table])))
                ->from($table)
                ->where($qb->expr()->in(
                    'id',
                    $qb->createNamedParameter(array_keys($ids), IQueryBuilder::PARAM_INT_ARRAY)
                ));

            $result = $qb->executeQuery();

            while ($record = $result->fetch()) {
                $recordsByTable[$table][(int)$record['id']] = $record;
            }

            $result->closeCursor();
        }

        foreach ($rows as $index => $row) {
            $values = is_array($row['customFields'] ?? null) ? $row['customFields'] : [];
            $labels = [];

            foreach ($targets as $technicalName => $target) {
                $id = $values[$technicalName] ?? null;
                $record = is_numeric($id) ? ($recordsByTable[$target['table']][(int)$id] ?? null) : null;

                if ($record === null) {
                    continue;
                }

                $label = '';

                foreach ($target['parts'] as $part) {
                    $label .= $part['text'] ?? (string)($record[$part['column']] ?? '');
                }

                $labels[$technicalName] = $label;
            }

            if ($labels !== []) {
                $rows[$index]['customFieldLabels'] = $labels;
            }
        }

        return $rows;
    }
}