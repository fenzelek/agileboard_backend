<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceTaxReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_tax_reports', function (Blueprint $table) {

            // Id
            $table->increments('id');

            // Data
            $table->unsignedInteger('invoice_id')->index();
            $table->unsignedInteger('vat_rate_id');
            $table->integer('price_net');
            $table->integer('price_gross');

            // Times
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('invoice_tax_reports');
    }
}
