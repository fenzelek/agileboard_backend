<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('sprint_id');
            $table->index('status_id');
            $table->index('ticket_id');
            $table->index('type_id');
            $table->index('assigned_id');
            $table->index('reporter_id');
            $table->index('priority');
        });

        Schema::table('sprints', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('status');
            $table->index('priority');
        });

        Schema::table('statuses', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('priority');
        });

        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->index('ticket_id');
            $table->index('user_id');
        });

        Schema::table('history', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('resource_id');
            $table->index('object_id');
            $table->index('field_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
