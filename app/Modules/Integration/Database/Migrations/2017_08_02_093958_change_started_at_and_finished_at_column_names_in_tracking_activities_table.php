<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStartedAtAndFinishedAtColumnNamesInTrackingActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_tracking_activities', function (Blueprint $table) {
            $table->renameColumn('started_at', 'utc_started_at');
            $table->renameColumn('finished_at', 'utc_finished_at');
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
            $table->renameColumn('utc_started_at', 'started_at');
            $table->renameColumn('utc_finished_at', 'finished_at');
        });
    }
}
