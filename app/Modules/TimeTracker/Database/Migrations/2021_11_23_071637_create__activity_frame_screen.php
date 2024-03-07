<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityFrameScreen extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_tracker_activity_frame_screen', function (Blueprint $table) {
            $table->integer('screenable_id');
            $table->string('screenable_type');
            $table->bigInteger('screen_id')->unsigned();
            $table->foreign('screen_id')->references('id')->on('time_tracker_screens')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_tracker_activity_frame_screen');
    }
}
