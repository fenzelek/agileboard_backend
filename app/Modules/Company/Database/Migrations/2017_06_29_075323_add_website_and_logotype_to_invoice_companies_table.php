<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWebsiteAndLogotypeToInvoiceCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_companies', function (Blueprint $table) {
            $table->string('website')->after('phone');
            $table->string('logotype')->after('website');
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
            $table->dropColumn(['logotype', 'website']);
        });
    }
}
