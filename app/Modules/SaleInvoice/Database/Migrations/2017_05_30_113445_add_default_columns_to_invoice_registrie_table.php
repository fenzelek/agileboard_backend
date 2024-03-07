<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultColumnsToInvoiceRegistrieTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_registries', function (Blueprint $table) {
            $table->boolean('default')->default(0)->after('company_id');
            $table->boolean('is_used')->default(0)->after('default');
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
            $table->dropColumn('default', 'is_used');
        });
    }
}
