<?php

declare(strict_types=1);

namespace OCA\SfxonItam\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010100Date20260411092800 extends SimpleMigrationStep {
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

        if (!$schema->hasTable('sfxon_device')) {
            $table = $schema->createTable('sfxon_device');
            
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

            $table->addColumn('device_status_id', Types::BIGINT, [
                'notnull' => false,
            ]);

            $table->addColumn('position_id', Types::BIGINT, [
                'notnull' => false,
            ]);

            $table->addColumn('device_type_id', Types::BIGINT, [
                'notnull' => false,
            ]);

            $table->addColumn('itam_user_id', Types::BIGINT, [
                'notnull' => false,
            ]);

            $table->addColumn('serial_number', Types::STRING, [
                'notnull' => false,
                'length' => 300,
            ]);

            $table->addColumn('serial_number2', Types::STRING, [
                'notnull' => false,
                'length' => 300,
            ]);

            $table->addColumn('asset_number', Types::STRING, [
                'notnull' => false,
                'length' => 300,
            ]);

            $table->addColumn('mac_address', Types::STRING, [
                'notnull' => false,
                'length' => 32, // A MAC Address usually has only 17 characters, when you count the colons. To make this field a power of 8, i decided to give it 32 chars.
            ]);

            $table->addColumn('merchant_id', Types::BIGINT, [
                'notnull' => false,
            ]);

            $table->addColumn('invoice_number', Types::STRING, [
                'notnull' => false,
                'length' => 300,
            ]);

            $table->addColumn('purchase_date', Types::DATE_IMMUTABLE, [
                'notnull' => false,
            ]);

            $table->addColumn('custom_fields', Types::TEXT, [
                'notnull' => false,
            ]);

            $table->addColumn('comment', Types::TEXT, [
                'notnull' => false,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['itam_user_id'], 'sfxon_device_uid');
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