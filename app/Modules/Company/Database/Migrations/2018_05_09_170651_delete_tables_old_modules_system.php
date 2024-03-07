<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteTablesOldModulesSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('package_payments');
        Schema::dropIfExists('company_application_settings');
        Schema::dropIfExists('package_application_settings');
        Schema::dropIfExists('application_settings');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('package_id');
            $table->dropColumn('package_until');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
