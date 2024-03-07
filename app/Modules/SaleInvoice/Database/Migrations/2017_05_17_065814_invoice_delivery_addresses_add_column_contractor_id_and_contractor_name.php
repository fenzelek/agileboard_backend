<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InvoiceDeliveryAddressesAddColumnContractorIdAndContractorName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_delivery_addresses', function (Blueprint $table) {
            $table->unsignedInteger('receiver_id')->nullable()->default(null)->after('invoice_id');
            $table->string('receiver_name')->nullable()->default(null)->after('receiver_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_delivery_addresses', function (Blueprint $table) {
            $table->dropColumn(['receiver_id', 'receiver_name']);
        });
    }
}
