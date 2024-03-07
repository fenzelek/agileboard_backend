<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIndexesOfNameAndShortNameInProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique('projects_name_unique');
            $table->dropUnique('projects_short_name_unique');
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'short_name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unique('name');
            $table->unique('short_name');
            $table->dropUnique('projects_company_id_name_unique');
            $table->dropUnique('projects_company_id_short_name_unique');
        });
    }
}
