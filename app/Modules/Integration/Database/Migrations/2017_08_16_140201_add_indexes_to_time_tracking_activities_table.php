<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToTimeTrackingActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_tracking_activities', function (Blueprint $table) {
            $table->unique(['integration_id','external_activity_id','utc_started_at'], 'unique_integration_activity');
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
            $table->dropIndex('unique_integration_activity');
        });
    }
}
