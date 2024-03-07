<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceInvoiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_invoice', function (Blueprint $table) {
            // Relation
            $table->unsignedInteger('parent_id')->index()->comment('Reference to main invoice from `invoices` table');
            $table->unsignedInteger('node_id')->index()->comment('Reference to related invoice from `invoices` table');

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
        Schema::drop('invoice_invoice');
    }
}
