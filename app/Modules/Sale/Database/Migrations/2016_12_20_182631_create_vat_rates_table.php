<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVatRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vat_rates', function (Blueprint $table) {

            // Id
            $table->increments('id')->comment('Cannot delete any record from this table');

            // Data
            $table->unsignedInteger('rate')->comment('Number need to calculations');
            $table->string('name', 63);
            $table->boolean('visible')->default(true)->comment('Used in front to show/hide vat rate in select element');

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
        Schema::drop('vat_rates');
    }
}
