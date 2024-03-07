<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToTimeTrackingUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_tracking_users', function (Blueprint $table) {
            $table->unique(['integration_id','external_user_id']);
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_tracking_users', function (Blueprint $table) {
            $table->dropIndex('time_tracking_users_integration_id_external_user_id_unique');
        });
    }
}
