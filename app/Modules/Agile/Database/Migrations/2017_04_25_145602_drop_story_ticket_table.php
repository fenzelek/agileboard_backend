<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropStoryTicketTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('story_ticket');
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('story_ticket', function (Blueprint $table) {
            $table->unsignedInteger('story_id');
            $table->unsignedInteger('ticket_id');
        });
    }
}
