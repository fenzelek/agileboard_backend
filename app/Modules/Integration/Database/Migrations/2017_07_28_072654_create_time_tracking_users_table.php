<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeTrackingUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_tracking_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('integration_id')->index();
            $table->unsignedInteger('user_id')->nullable()->default(null);
            $table->string('external_user_id')->default('')->index();
            $table->string('external_user_email')->default('');
            $table->string('external_user_name')->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('time_tracking_users');
    }
}
