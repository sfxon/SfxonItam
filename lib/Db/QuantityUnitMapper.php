<?php declare(strict_types=1);

namespace OCA\SfxonItam\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<QuantityUnit>
 */
class QuantityUnitMapper extends QBMapper
{
    use TSfxonEntityMapper;
    use TSfxonEntityMapperWithNameFilter;

    private const TABLE_NAME = 'sfxon_quantity_unit';
    private const TABLE_ALIAS = 'qu';
    private array $allowedEntityIdFields = [];
    private array $allowedSortColumns = [
        'name',
    ];
    private const JOIN_FILTERS = [];

    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE_NAME, QuantityUnit::class);
    }
}