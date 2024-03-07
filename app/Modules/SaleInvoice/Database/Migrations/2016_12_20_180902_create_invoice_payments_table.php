<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_payments', function (Blueprint $table) {

            // Id
            $table->increments('id');

            //Data
            $table->unsignedInteger('invoice_id')->index();
            $table->integer('amount');
            $table->unsignedInteger('payment_method_id');
            $table->unsignedInteger('registrar_id'); //created_by

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
        Schema::drop('invoice_payments');
    }
}
