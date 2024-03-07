<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToTimeTrackingNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_tracking_notes', function (Blueprint $table) {
            $table->unique(['integration_id','external_note_id']);
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
            $table->dropIndex('time_tracking_notes_integration_id_external_note_id_unique');
        });
    }
}
