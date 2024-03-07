<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeZipCodeLengthInInvoiceDeliveryAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_delivery_addresses', function (Blueprint $table) {
            $table->string('zip_code', 255)->change();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_delivery_addresses', function (Blueprint $table) {
            $table->string('zip_code', 7)->change();
        });
    }
}
