<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountryVatinPrefixToInvoiceCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_companies', function (Blueprint $table) {
            $table->string('country_vatin_prefix_id')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_companies', function (Blueprint $table) {
            $table->dropColumn('country_vatin_prefix_id');
        });
    }
}
