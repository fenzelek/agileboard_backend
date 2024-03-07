<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeTrackingActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_tracking_activities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('integration_id');
            $table->unsignedInteger('user_id')->nullable()->default(null);
            $table->unsignedInteger('project_id')->nullable()->default(null);
            $table->unsignedInteger('ticket_id')->nullable()->default(null);
            $table->unsignedInteger('external_activity_id');
            $table->unsignedInteger('time_tracking_user_id')->nullable()->default(null);
            $table->unsignedInteger('time_tracking_project_id')->nullable()->default(null);
            $table->unsignedInteger('time_tracking_note_id')->nullable()->default(null);
            $table->timestamp('started_at')->nullable()->default(null)->comment('Start time in UTC');
            $table->timestamp('finished_at')->nullable()->default(null)->comment('End time in UTC');
            $table->unsignedInteger('tracked')->default(0)->comment('Time tracked in seconds');
            $table->unsignedInteger('activity')->default(0)->comment('User activity in seconds');
            $table->string('comment')->default('')->comment('Extra manual comment for this activity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('time_tracking_activities');
    }
}
