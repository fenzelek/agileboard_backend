<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTimeTrackingNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_tracking_notes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('integration_id')->index();
            $table->string('external_note_id')->default('')->index();
            $table->string('external_project_id')->default('');
            $table->string('external_user_id')->default('');
            $table->string('content')->default('');
            $table->timestamp('recorded_at')->nullable()->default(null)->comment('Time of note in UTC');
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
        Schema::drop('time_tracking_notes');
    }
}
