<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {

            // Id
            $table->increments('id');

            // Numbers
            $table->string('number', 63);
            $table->unsignedInteger('order_number');

            // Registry
            /*
             * Pointer to used registry.
             */
            $table->unsignedInteger('invoice_registry_id')->index();

            // Ownerships
            $table->unsignedInteger('drawer_id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('company_snap_id');
            $table->unsignedInteger('contractor_id')->index();
            $table->unsignedInteger('contractor_snap_id');

            // Transaction times
            $table->timestamp('sale_date');
            $table->timestamp('issue_date');

            // Invoice type
            $table->unsignedInteger('invoice_type_id')->index();

            // Data
            $table->integer('price_net');
            $table->integer('price_gross');
            $table->integer('vat_sum');
            $table->integer('payment_left');

            // Payments
            $table->unsignedInteger('payment_term_days');
            $table->unsignedInteger('payment_method_id');

            // Is paid?
            $table->boolean('is_paid')->default(false);

            // Last used
            $table->timestamp('last_printed_at');
            $table->timestamp('last_send_at');

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
        Schema::drop('invoices');
    }
}
