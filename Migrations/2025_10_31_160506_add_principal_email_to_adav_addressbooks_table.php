<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class AddPrincipalEmailToAdavAddressbooksTable extends Migration
{
    protected function indexExists($tableName, $indexName)
    {
        $sPrefix = Capsule::connection()->getTablePrefix();
        Capsule::connection()->setTablePrefix('');
        $result = Capsule::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', Capsule::connection()->raw('DATABASE()'))
            ->where('TABLE_NAME', $sPrefix . $tableName)
            ->where('INDEX_NAME', $indexName)
            ->exists();
        Capsule::connection()->setTablePrefix($sPrefix);

        return $result;
    }

    /**
     * Run the migrations.
     *
     * Adds a STORED generated column principal_email AS (SUBSTRING(principaluri, 12))
     * and creates an index idx_principal_email on it.
     *
     * (We use raw statements because Laravel schema builder doesn't support generated columns directly.)
     *
     * @return void
     */
    public function up()
    {
        $tableName = 'adav_addressbooks';
        $columnName = 'principal_email';
        $indexName = 'idx_principal_email';

        // Check if the column already exists
        if (!Capsule::schema()->hasColumn($tableName, $columnName)) {
            Capsule::schema()->table($tableName, function (Blueprint $table) use ($columnName) {
                $table->string($columnName, 255)
                    ->storedAs('SUBSTRING(principaluri, 12)')
                    ->after('principaluri');
            });
        }

        // Create the index if it does not exist
        if (!$this->indexExists($tableName, $indexName)) {
            Capsule::schema()->table($tableName, function (Blueprint $table) use ($columnName, $indexName) {
                $table->index($columnName, $indexName);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = 'adav_addressbooks';
        $columnName = 'principal_email';
        $indexName = 'idx_principal_email';

        // Drop the index if it exists
        if ($this->indexExists($tableName, $indexName)) {
            Capsule::schema()->table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }

        // Remove the column if it exists
        if (Capsule::schema()->hasColumn($tableName, $columnName)) {
            Capsule::schema()->table($tableName, function (Blueprint $table) use ($columnName) {
                $table->dropColumn($columnName);
            });
        }
    }
}
