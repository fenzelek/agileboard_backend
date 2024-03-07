<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToTimeTrackingProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_tracking_projects', function (Blueprint $table) {
            $table->unique(['integration_id','external_project_id']);
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_tracking_projects', function (Blueprint $table) {
            $table->dropIndex('time_tracking_projects_integration_id_external_project_id_unique');
        });
    }
}
