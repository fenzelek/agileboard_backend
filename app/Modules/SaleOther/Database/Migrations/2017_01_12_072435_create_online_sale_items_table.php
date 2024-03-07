<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOnlineSaleItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('online_sale_items', function (Blueprint $table) {

            // Id
            $table->increments('id');

            // Invoice
            $table->unsignedInteger('online_sale_id')->index();

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

            // Times
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
        Schema::dropIfExists('online_sale_items');
    }
}
