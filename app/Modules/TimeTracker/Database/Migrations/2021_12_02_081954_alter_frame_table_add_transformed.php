<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterFrameTableAddTransformed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('ALTER TABLE time_tracker_frames MODIFY COLUMN `from` TIMESTAMP NOT NULL DEFAULT 0');
        \DB::statement('ALTER TABLE time_tracker_frames MODIFY COLUMN `to` TIMESTAMP NOT NULL DEFAULT 0');

        Schema::table('time_tracker_frames', function (Blueprint $table) {
            $table->boolean('transformed')->after('activity')->default(false);
            $table->integer('counter_сhecks')->after('transformed')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_tracker_frames', function (Blueprint $table) {
            $table->dropColumn('transformed');
            $table->dropColumn('counter_сhecks');
        });
    }
}
