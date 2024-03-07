<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceOnlineSaleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_online_sale', function (Blueprint $table) {
            // Relation
            $table->unsignedInteger('invoice_id')->index()->comment('Reference to main invoice from `invoices` table');
            $table->unsignedInteger('online_sale_id')->index()->comment('Reference to related online sale from `online_sales` table');

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
        Schema::drop('invoice_online_sale');
    }
}
