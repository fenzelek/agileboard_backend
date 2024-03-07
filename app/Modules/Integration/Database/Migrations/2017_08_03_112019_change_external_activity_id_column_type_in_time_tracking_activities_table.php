<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeExternalActivityIdColumnTypeInTimeTrackingActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_tracking_activities', function (Blueprint $table) {
            $table->string('external_activity_id')->change();
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
            $table->unsignedInteger('external_activity_id')->change();
        });
    }
}
