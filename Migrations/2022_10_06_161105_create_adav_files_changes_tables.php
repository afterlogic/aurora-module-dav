<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateAdavFilesChangesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sPrefix = Capsule::connection()->getTablePrefix();

        $sSql = str_replace("%PREFIX%", $sPrefix,
"CREATE TABLE IF NOT EXISTS `%PREFIX%adav_files_changes` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principaluri VARBINARY(255) NOT NULL,
    uri VARBINARY(255) NOT NULL,
    synctoken INT(11) UNSIGNED NOT NULL,
    storage VARBINARY(255) NOT NULL,
    operation TINYINT(1) NOT NULL,
    INDEX principaluri_storage_synctoken (principaluri, storage, synctoken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        Capsule::connection()->statement($sSql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('adav_files_changes');
    }
}
