<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class AddDavIndexies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            Capsule::schema()->table('adav_cards', function (Blueprint $table) {
                $table->index('addressbookid', 'addressbookid_index');
                $table->index('lastmodified', 'lastmodified_index');
                $table->index('uri', 'uri_index');
            });
        } catch (\Exception $e) {
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->table('adav_cards', function (Blueprint $table) {
            $table->dropIndex('addressbookid_index');
            $table->dropIndex('lastmodified_index');
            $table->dropIndex('uri_index');
        });
    }
}
