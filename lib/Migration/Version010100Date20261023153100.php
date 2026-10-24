<?php declare(strict_types=1);

namespace OCA\SfxonItam\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010100Date20261023153100 extends SimpleMigrationStep {
    private const CUSTOM_FIELD_GROUP_TABLE = 'sfxon_custom_field_group';

    /** @var array<string, string> entity table => display name */
    private const CUSTOM_FIELD_GROUPS = [
        'sfxon_purchase' => 'Purchase',
        'sfxon_transaction' => 'Purchase Transaction',
        'sfxon_transaction_type' => 'Purchase Transaction Type',
        'sfxon_transaction_document' => 'Purchase Transaction Document',
    ];

    /** @var array<string, int> technical key => sort_order */
    private const SYSTEM_TRANSACTION_TYPES = [
        'order' => 10,
        'delivery' => 20,
        'refund' => 30,
        'bonus' => 40,
        'sale' => 50,
        'disposal' => 60,
        'repair' => 70,
    ];

    public function __construct(private IDBConnection $db) {
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('sfxon_purchase')) {
            $table = $schema->createTable('sfxon_purchase');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
                'unsigned' => true,
            ]);

            $table->addColumn('name', Types::STRING, [
                'notnull' => false,
                'length' => 300,
            ]);

            $table->addColumn('purchase_date', Types::DATE_IMMUTABLE, [
                'notnull' => false,
            ]);

            $table->addColumn('last_day_of_guarantee', Types::DATE_IMMUTABLE, [
                'notnull' => false,
            ]);

            $table->addColumn('merchant_id', Types::BIGINT, [
                'notnull' => false,
                'default' => null,
                'unsigned' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['merchant_id'], 'sfxon_pu_mid');
        }

        if (!$schema->hasTable('sfxon_transaction_type')) {
            $table = $schema->createTable('sfxon_transaction_type');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
                'unsigned' => true,
            ]);

            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 300,
            ]);

            $table->addColumn('comment', Types::TEXT, [
                'notnull' => false,
            ]);

            $table->addColumn('sort_order', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
            ]);

            $table->addColumn('is_system', Types::SMALLINT, [
                'notnull' => true,
                'default' => 0,
            ]);

            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('sfxon_transaction')) {
            $table = $schema->createTable('sfxon_transaction');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
                'unsigned' => true,
            ]);

            $table->addColumn('purchase_id', Types::BIGINT, [
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->addColumn('transaction_type_id', Types::BIGINT, [
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->addColumn('transaction_date', Types::DATE_IMMUTABLE, [
                'notnull' => false,
            ]);

            $table->addColumn('sort_order', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
            ]);

            $table->addColumn('comment', Types::TEXT, [
                'notnull' => false,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['purchase_id'], 'sfxon_tr_pid');
            $table->addIndex(['transaction_type_id'], 'sfxon_tr_tid');
        }

        if (!$schema->hasTable('sfxon_transaction_document')) {
            $table = $schema->createTable('sfxon_transaction_document');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
                'unsigned' => true,
            ]);

            $table->addColumn('transaction_id', Types::BIGINT, [
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->addColumn('document_id', Types::BIGINT, [
                'notnull' => false,
                'default' => null,
                'unsigned' => true,
            ]);

            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 300,
            ]);

            $table->addColumn('document_date', Types::DATE_IMMUTABLE, [
                'notnull' => false,
            ]);

            $table->addColumn('document_number_external', Types::STRING, [
                'notnull' => false,
                'length' => 300,
            ]);

            $table->addColumn('document_number_internal', Types::STRING, [
                'notnull' => false,
                'length' => 300,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['transaction_id'], 'sfxon_td_trid');
        }

        return $schema;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
        $this->seedCustomFieldGroups($schemaClosure);
        $this->seedSystemTransactionTypes();
    }

    private function seedCustomFieldGroups(Closure $schemaClosure): void {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable(self::CUSTOM_FIELD_GROUP_TABLE)) {
            throw new \RuntimeException('Table ' . self::CUSTOM_FIELD_GROUP_TABLE . ' does not exist, cannot seed custom field groups.');
        }

        $groupTable = $schema->getTable(self::CUSTOM_FIELD_GROUP_TABLE);

        foreach (['entity_name', 'name'] as $column) {
            if (!$groupTable->hasColumn($column)) {
                throw new \RuntimeException(sprintf(
                    'Column %s.%s does not exist, cannot seed custom field groups.',
                    self::CUSTOM_FIELD_GROUP_TABLE,
                    $column
                ));
            }
        }

        foreach (self::CUSTOM_FIELD_GROUPS as $entityName => $name) {
            $qb = $this->db->getQueryBuilder();
            $qb->select('id')
                ->from(self::CUSTOM_FIELD_GROUP_TABLE)
                ->where($qb->expr()->eq('entity_name', $qb->createNamedParameter($entityName)))
                ->setMaxResults(1);
            $result = $qb->executeQuery();
            $existingId = $result->fetchOne();
            $result->closeCursor();

            if ($existingId !== false) {
                continue;
            }

            $qb = $this->db->getQueryBuilder();
            $qb->insert(self::CUSTOM_FIELD_GROUP_TABLE)
                ->values([
                    'entity_name' => $qb->createNamedParameter($entityName),
                    'name' => $qb->createNamedParameter($name),
                ])
                ->executeStatement();
        }
    }

    private function seedSystemTransactionTypes(): void {
        $table = 'sfxon_transaction_type';

        foreach (self::SYSTEM_TRANSACTION_TYPES as $name => $sortOrder) {
            $qb = $this->db->getQueryBuilder();
            $qb->select('id')
                ->from($table)
                ->where($qb->expr()->eq(
                    $qb->func()->lower('name'),
                    $qb->createNamedParameter(strtolower($name))
                ))
                ->setMaxResults(1);
            $result = $qb->executeQuery();
            $existingId = $result->fetchOne();
            $result->closeCursor();

            $qb = $this->db->getQueryBuilder();

            if ($existingId !== false) {
                $qb->update($table)
                    ->set('is_system', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
                    ->set('sort_order', $qb->createNamedParameter($sortOrder, IQueryBuilder::PARAM_INT))
                    ->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$existingId, IQueryBuilder::PARAM_INT)))
                    ->executeStatement();
                continue;
            }

            $qb->insert($table)
                ->values([
                    'name' => $qb->createNamedParameter($name),
                    'sort_order' => $qb->createNamedParameter($sortOrder, IQueryBuilder::PARAM_INT),
                    'is_system' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
                ])
                ->executeStatement();
        }
    }
}