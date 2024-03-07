<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_companies', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('invoice_id')->index();
            $table->unsignedInteger('company_id');
            $table->string('name', 255);
            $table->string('vatin', 15);
            $table->string('email', 255);
            $table->string('phone', 15);
            $table->string('bank_name', 63);
            $table->string('bank_account_number', 63);

            // Addresses
            $table->string('main_address_street', 255);
            $table->string('main_address_number', 31);
            $table->string('main_address_zip_code', 7);
            $table->string('main_address_city', 63);
            $table->string('main_address_country', 63);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_companies');
    }
}
