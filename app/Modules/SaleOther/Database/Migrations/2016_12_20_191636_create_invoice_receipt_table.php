<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceReceiptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_receipt', function (Blueprint $table) {
            // Relation
            $table->unsignedInteger('invoice_id')->index()->comment('Reference to main invoice from `invoices` table');
            $table->unsignedInteger('receipt_id')->index()->comment('Reference to related receipt from `receipts` table');

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
        Schema::drop('invoice_receipt');
    }
}
