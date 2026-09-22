<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

interface ISortableEntity {
    /**
     * @return array{
     *   table: string,
     *   columns: list<string|array{join: string, column: string}>,
     *   joins?: array<string, array{table: string, localKey: string}>
     * }
     */
    public static function getSortDefinition(): array;
}