<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReceiptItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipt_items', function (Blueprint $table) {

            // Id
            $table->increments('id');

            // CreateInvoice
            $table->unsignedInteger('receipt_id')->index();

            // Service
            $table->unsignedInteger('company_service_id');

            // Data
            $table->string('name', 255);
            $table->integer('price_net');
            $table->integer('price_net_sum');
            $table->integer('price_gross');
            $table->integer('price_gross_sum');
            $table->string('vat_rate', 63);
            $table->unsignedInteger('vat_rate_id');
            $table->integer('vat_sum');
            $table->integer('quantity');

            // Ownerships
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('editor_id');

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
        Schema::drop('receipt_items');
    }
}
