<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommunicationChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('communication_channels', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('project_id')->nullable()->index();
            $table->unsignedInteger('communication_channel_type_id')->nullable()
                ->index();
            $table->index(['company_id', 'user_id']);
            $table->boolean('notifications_enabled')->default(false);
            $table->string('value', 255);
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('communication_channels');
    }
}
