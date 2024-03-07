<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimeTrackingManualActivityHistoryTable extends Migration
{
    /** time_tracking_manual_activity_history
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_tracking_manual_activity_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('author_id')->comment('The author of the manual entry');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('project_id');
            $table->unsignedInteger('ticket_id');
            $table->dateTime('from')->comment('Start time');
            $table->dateTime('to')->comment('End time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_tracking_manual_activity_history');
    }
}
