<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountryVatinPrefixColumnToInvoiceContractorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_contractors', function (Blueprint $table) {
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
        Schema::table('invoice_contractors', function (Blueprint $table) {
            $table->dropColumn('country_vatin_prefix_id');
        });
    }
}
