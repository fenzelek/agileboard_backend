<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLockedUserIdColumnToTimeTrackingActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_tracking_activities', function (Blueprint $table) {
            $table->unsignedInteger('locked_user_id')->nullable()->default(null)->after('ticket_id')->index();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_tracking_activities', function (Blueprint $table) {
            $table->dropColumn('locked_user_id');
        });
    }
}
