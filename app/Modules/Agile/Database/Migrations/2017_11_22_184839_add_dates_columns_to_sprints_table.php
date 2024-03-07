<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDatesColumnsToSprintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->dateTime('planned_activation')->nullable()->after('priority');
            $table->dateTime('planned_closing')->nullable()->after('planned_activation');
            $table->dateTime('activated_at')->nullable()->after('planned_closing');
            $table->dateTime('closed_at')->nullable()->after('activated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->dropColumn('planned_activation');
            $table->dropColumn('planned_closing');
            $table->dropColumn('activated_at');
            $table->dropColumn('closed_at');
        });
    }
}
