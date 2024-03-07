<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InvoiceCompaniesNullableBankDetailsColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_companies', function (Blueprint $table) {
            $table->string('bank_name', 63)->nullable()->default(null)->change();
            $table->string('bank_account_number', 63)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_companies', function (Blueprint $table) {
            $table->string('bank_name', 63)->change();
            $table->string('bank_account_number', 63)->change();
        });
    }
}
