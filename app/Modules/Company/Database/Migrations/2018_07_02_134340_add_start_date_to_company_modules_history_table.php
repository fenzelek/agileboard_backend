<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStartDateToCompanyModulesHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_modules_history', function (Blueprint $table) {
            $table->dateTime('start_date')->nullable()->after('new_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_modules_history', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });
    }
}
