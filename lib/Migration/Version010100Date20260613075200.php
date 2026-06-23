<?php

declare(strict_types=1);

namespace OCA\SfxonItam\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010100Date20260613075200 extends SimpleMigrationStep {
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

        if (!$schema->hasTable('sfxon_custom_field')) {
            $table = $schema->createTable('sfxon_custom_field');
            
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
                'unsigned' => true,
            ]);

            $table->addColumn('custom_field_group_id', Types::BIGINT, [
                'notnull' => false,
            ]);

            $table->addColumn('technical_name', Types::STRING, [
                'notnull' => false,
                'length' => 32,
            ]);

            $table->addColumn('name', Types::STRING, [
                'notnull' => false,
                'length' => 300,
            ]);

            $table->addColumn('type', Types::STRING, [
                'notnull' => false,
                'length' => 16,
            ]);

            $table->addColumn('position', Types::INTEGER, [
                'notnull' => true,
                'length' => 11,
                'default' => 0
            ]);

            $table->addColumn('options', Types::TEXT, [
                'notnull' => false,
            ]);

            $table->addColumn('editable', Types::BOOLEAN, [
                'notnull' => true,
                'default' => true
            ]);

            $table->addColumn('validation', Types::TEXT, [
                'notnull' => false,
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
}
}