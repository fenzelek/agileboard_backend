<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetPrefixToUniqueInCompanyForInvoiceRegistriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_registries', function (Blueprint $table) {
            $table->unique(['prefix', 'company_id']);
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_registries', function (Blueprint $table) {
            $table->dropUnique('invoice_registries_prefix_company_id_unique');
        });
    }
}
