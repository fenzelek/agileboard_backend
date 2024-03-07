<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDescriptionAndPrintOnInvoiceColumnsToCompanyServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_services', function (Blueprint $table) {
            $table->boolean('print_on_invoice')->after('name');
            $table->text('description')->nullable()->after('print_on_invoice');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_services', function (Blueprint $table) {
            $table->dropColumn(['description', 'print_on_invoice']);
        });
    }
}
