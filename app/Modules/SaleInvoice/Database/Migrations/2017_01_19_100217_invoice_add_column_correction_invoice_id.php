<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InvoiceAddColumnCorrectionInvoiceId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('corrected_invoice_id')->nullable()->default(null)->after('contractor_id');
            $table->integer('payment_left')->nullable()->default(null)->change();
            $table->unsignedInteger('payment_term_days')->nullable()->default(null)->change();
            $table->unsignedInteger('payment_method_id')->nullable()->default(null)->change();
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
            $table->dropColumn('corrected_invoice_id');
            DB::statement('ALTER TABLE  invoices MODIFY payment_left INTEGER NOT NULL ');
            DB::statement('ALTER TABLE  invoices MODIFY payment_term_days INTEGER UNSIGNED NOT NULL ');
            DB::statement('ALTER TABLE  invoices MODIFY payment_method_id INTEGER UNSIGNED NOT NULL ');
        });
    }
}
