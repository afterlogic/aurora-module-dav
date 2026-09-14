<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class ConvertAdavCalendarinstancesTableToUtf8mb4 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $prefix = Capsule::connection()->getTablePrefix();
        Capsule::connection()->statement(
            "ALTER TABLE {$prefix}adav_calendarinstances CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $prefix = Capsule::connection()->getTablePrefix();
        Capsule::connection()->statement(
            "ALTER TABLE {$prefix}adav_calendarinstances CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci"
        );
    }
}
