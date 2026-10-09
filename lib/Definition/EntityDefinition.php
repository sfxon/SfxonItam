<?php declare(strict_types=1);

namespace OCA\SfxonItam\Definition;

/**
 * Describes how an entity is handled in the UI/API (route, list, template, custom field group, deletion rules).
 * Knowledge regarding tables and columns remains in lib/Db.
 */
abstract class EntityDefinition
{
    public const DEFAULT_LIST_INCLUDES = [
        'deviceStatus' => [],
        'deviceType' => [],
        'itamUser' => ['fields' => ['id', 'firstname', 'lastname']],
        'merchant' => [],
        'position' => [
            'fields' => ['id', 'name', 'location_id'],
            'with' => [
                'location' => [
                    'table' => 'sfxon_location',
                    'localKey' => 'location_id',
                    'fields' => ['id', 'name'],
                ],
            ],
        ],
        'quantityUnit' => [],
    ];

    /**
     * @param string $key Unique key, e.g. 'deviceStatus'
     * @param string $label Display Name for error messages
     * @param string $route URL-Slug
     * @param class-string $entityClass
     * @param class-string $mapperClass
     * @param class-string $serviceClass
     * @param string $customFieldGroup e.g. 'sfxon_device_status'
     * @param list<string> $expectedFields
     * @param array<string, mixed> $listIncludes
     * @param list<DeleteGuard> $deleteGuards
     * @param list<string> $labelFields Properties joined to the display label, e.g. ['firstname', 'lastname']
     * @param string|null $labelParent Relation whose name is prefixed to the label, e.g. 'location' for positions
     * @param string $labelSeparator Separator between parent name and own label
     * @param list<string> $searchFields Properties searched in dropdowns (default: $labelFields)
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $route,
        public readonly string $entityClass,
        public readonly string $mapperClass,
        public readonly string $serviceClass,
        public readonly string $customFieldGroup,
        public readonly array $expectedFields = [],
        public readonly string $defaultOrderBy = 'name',
        public readonly array $listIncludes = [],
        public readonly array $deleteGuards = [],
        public readonly array $labelFields = ['name'],
        public readonly ?string $labelParent = null,
        public readonly string $labelSeparator = ' - ',
        public readonly array $searchFields = [],
    ) {
    }

    public function listId(): string
    {
        return $this->route . '-list';
    }

    public function templateDir(): string
    {
        return $this->route;
    }
}