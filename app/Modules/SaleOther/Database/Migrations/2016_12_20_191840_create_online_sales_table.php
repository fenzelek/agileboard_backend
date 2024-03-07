<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOnlineSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('online_sales', function (Blueprint $table) {

            // Id
            $table->increments('id');

            // Numbers
            $table->string('number', 63);
            $table->string('transaction_number', 63);

            // Ownerships
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('company_snap_id');

            // Transaction times
            $table->timestamp('sale_date');

            // Data
            $table->integer('price_net');
            $table->integer('price_gross');
            $table->integer('vat_sum');

            // Payments
            $table->unsignedInteger('payment_method_id');

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
        Schema::drop('online_sales');
    }
}
