<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveColumnVatFromModPriceAndCompanyModuleHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mod_prices', function (Blueprint $table) {
            $table->dropColumn('vat');
        });

        Schema::table('company_modules_history', function (Blueprint $table) {
            $table->dropColumn('vat');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mod_prices', function (Blueprint $table) {
            $table->integer('vat')->nullable();
        });

        Schema::table('company_modules_history', function (Blueprint $table) {
            $table->integer('vat')->nullable();
        });
    }
}
