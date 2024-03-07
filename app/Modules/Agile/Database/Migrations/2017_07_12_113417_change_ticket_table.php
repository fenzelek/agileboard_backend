<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTicketTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->unsignedInteger('assigned_id')->default(null)->nullable()->change();
            $table->unsignedInteger('reporter_id')->default(null)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedInteger('assigned_id')->default(0)->change();
            $table->unsignedInteger('reporter_id')->change();
        });
    }
}
