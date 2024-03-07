<?php

use Illuminate\Database\Migrations\Migration;

class InvoiceAddDefaultValueForAddOrderNumberDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('invoices')->update(['order_number_date' => DB::raw('`issue_date`')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('invoices')->update(['order_number_date' => '']);
    }
}
