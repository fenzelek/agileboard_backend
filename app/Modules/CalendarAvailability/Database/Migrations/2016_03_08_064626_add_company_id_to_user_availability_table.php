<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCompanyIdToUserAvailabilityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_availability', function (Blueprint $table) {
            $table->unsignedInteger('company_id')->after('user_id')->index();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_availability', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
}
