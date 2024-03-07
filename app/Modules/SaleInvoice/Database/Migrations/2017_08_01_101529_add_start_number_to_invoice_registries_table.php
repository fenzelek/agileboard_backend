<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStartNumberToInvoiceRegistriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_registries', function (Blueprint $table) {
            $table->integer('start_number')->nullable()->after('is_used');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_registries', function (Blueprint $table) {
            $table->dropColumn('start_number');
        });
    }
}
