<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InvoicesChangeColumnTypeDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('sale_date')->nullable()->default(null)->change();
            $table->date('issue_date')->nullable()->default(null)->change();

            DB::statement('ALTER TABLE  invoices MODIFY last_printed_at TIMESTAMP NULL ');
            DB::statement('ALTER TABLE  invoices MODIFY last_send_at TIMESTAMP NULL ');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            DB::statement('ALTER TABLE  invoices MODIFY sale_date TIMESTAMP NOT NULL ');
            DB::statement('ALTER TABLE  invoices MODIFY issue_date TIMESTAMP NOT NULL ');
            DB::statement('ALTER TABLE  invoices MODIFY last_printed_at TIMESTAMP NOT NULL ');
            DB::statement('ALTER TABLE  invoices MODIFY last_send_at TIMESTAMP NOT NULL ');
        });
    }
}
