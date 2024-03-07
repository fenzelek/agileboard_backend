<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InvoiceItemsAddIsCorrection extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->integer('price_gross')->nullable()->after('price_net_sum');
            $table->integer('price_net')->nullable()->change();
            $table->boolean('is_correction')->default(false)->after('quantity');
            $table->unsignedInteger('position_corrected_id')->nullable()->default(null)->after('is_correction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'price_gross',
                'is_correction',
                'position_corrected_id',
            ]);
            $table->integer('price_net')->nullable(false)->change();
        });
    }
}
