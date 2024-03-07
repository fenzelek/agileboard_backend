<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('role_id');
            $table->string('token', 255);
            $table->string('domain')->nullable()
                ->comment('Allowed domain for external request');
            $table->string('ip_from', 15)->nullable()
                ->comment('Allowed IP start for external request');
            $table->string('ip_to', 15)->nullable()
                ->comment('Allowed IP end for external request');
            $table->unsignedInteger('ttl')
                ->comment('Time in minutes how long token after securing will be considered as valid');
            $table->nullableTimestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('company_tokens');
    }
}
