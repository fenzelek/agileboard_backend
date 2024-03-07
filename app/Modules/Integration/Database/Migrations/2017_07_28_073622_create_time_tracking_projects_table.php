<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeTrackingProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_tracking_projects', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('integration_id')->index();
            $table->unsignedInteger('project_id')->nullable()->default(null);
            $table->string('external_project_id')->default('')->index();
            $table->string('external_project_name')->default('');
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
        Schema::drop('time_tracking_projects');
    }
}
