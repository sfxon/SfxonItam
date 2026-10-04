<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\IDBConnection;

class LocationMapper extends EntityMapperAbstract

{
    use TSfxonEntityMapper;
    use TSfxonEntityMapperWithNameFilter;

    private const TABLE_NAME = 'sfxon_location';
    private const TABLE_ALIAS = 'lo';
    private array $allowedEntityIdFields = [];
    private array $allowedSortColumns = [
        'name',
    ];
    private const JOIN_FILTERS = [];

    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE_NAME, Location::class);
    }
}