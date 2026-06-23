<?php

declare(strict_types=1);

namespace OCA\SfxonItam\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010100Date20260613074800 extends SimpleMigrationStep {
    public function __construct(
        private IDBConnection $db,
    ) {}

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

        if (!$schema->hasTable('sfxon_custom_field_group')) {
            $table = $schema->createTable('sfxon_custom_field_group');
            
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
                'unsigned' => true,
            ]);

            $table->addColumn('entity_name', Types::STRING, [
                'notnull' => false,
                'length' => 300,
            ]);

            $table->addColumn('name', Types::STRING, [
                'notnull' => false,
                'length' => 300,
            ]);

            $table->addColumn('comment', Types::TEXT, [
                'notnull' => false,
            ]);

            $table->setPrimaryKey(['id']);
        }

        return $schema;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('sfxon_custom_field_group')
            ->values([
                'entity_name' => $qb->createNamedParameter('sfxon_device'),
                'name' => $qb->createNamedParameter('Device'),
            ])
        ->executeStatement();
    }
}