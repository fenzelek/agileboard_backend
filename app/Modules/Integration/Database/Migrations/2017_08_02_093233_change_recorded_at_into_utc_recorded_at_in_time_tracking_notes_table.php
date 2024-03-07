<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeRecordedAtIntoUtcRecordedAtInTimeTrackingNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_tracking_notes', function (Blueprint $table) {
            $table->renameColumn('recorded_at', 'utc_recorded_at');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_tracking_notes', function (Blueprint $table) {
            $table->renameColumn('utc_recorded_at', 'recorded_at');
        });
    }
}
