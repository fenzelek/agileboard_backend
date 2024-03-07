<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_permissions', function (Blueprint $table) {
            $table->unsignedInteger('project_id')->primary();
            $table->jsonb('ticket_create')->nullable();
            $table->jsonb('ticket_update')->nullable();
            $table->jsonb('ticket_destroy')->nullable();
            $table->jsonb('ticket_comment_create')->nullable();
            $table->jsonb('ticket_comment_update')->nullable();
            $table->jsonb('ticket_comment_destroy')->nullable();
            $table->jsonb('owner_ticket_show')->nullable();
            $table->jsonb('admin_ticket_show')->nullable();
            $table->jsonb('user_ticket_show')->nullable();
            $table->jsonb('client_ticket_show')->nullable();
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
        Schema::dropIfExists('project_permissions');
    }
}
