<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableTicketTicket extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_ticket', function (Blueprint $table) {
            $table->unsignedInteger('main_ticket_id');
            $table->unsignedInteger('sub_ticket_id');
            $table->timestamps();
            $table->primary(['main_ticket_id', 'sub_ticket_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_ticket');
    }
}
