<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddOwnershipsAndDeletedAttrsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {

            // Ownerships
            $table->unsignedInteger('remover_id')->nullable()->after('deleted');
            $table->unsignedInteger('editor_id')->nullable()->after('deleted');
            $table->unsignedInteger('creator_id')->nullable()->after('deleted');

            // SoftDelete
            /*
             * This fields is removed because we use own deleted attribute.
             */
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'creator_id',
                'editor_id',
                'remover_id',
            ]);
        });
    }
}
