<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedInteger('project_id')->after('id');
            $table->unsignedInteger('sprint_id')->default(0)->after('project_id');
            $table->unsignedInteger('status_id')->default(0)->after('sprint_id');
            $table->string('title')->nullable()->after('name');
            $table->unsignedInteger('type_id')->after('title');
            $table->unsignedInteger('assigned_id')->default(0)->after('type_id');
            $table->unsignedInteger('reporter_id')->after('assigned_id');
            $table->string('description')->nullable()->after('reporter_id');
            $table->unsignedInteger('estimate_time')->default(0)->after('description')
                ->comment('Estimated time in seconds');
            $table->boolean('hidden')->default(0)->after('estimate_time');
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
            $table->dropColumn('project_id');
            $table->dropColumn('sprint_id');
            $table->dropColumn('status_id');
            $table->dropColumn('title');
            $table->dropColumn('type_id');
            $table->dropColumn('assigned_id');
            $table->dropColumn('reporter_id');
            $table->dropColumn('description');
            $table->dropColumn('estimate_time');
            $table->dropColumn('hidden');
        });
    }
}
