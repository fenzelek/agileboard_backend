<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CompanyServicesAddColumnsPriceNetPriceGross extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_services', function (Blueprint $table) {
            $table->integer('price_net')->nullable()->default(null)->after('pkwiu');
            $table->integer('price_gross')->nullable()->default(null)->after('price_net');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_services', function (Blueprint $table) {
            $table->dropColumn(['price_net', 'price_gross']);
        });
    }
}
