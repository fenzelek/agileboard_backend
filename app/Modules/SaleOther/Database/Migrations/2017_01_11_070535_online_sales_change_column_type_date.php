<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OnlineSalesChangeColumnTypeDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('online_sales', function (Blueprint $table) {
            $table->dateTime('sale_date')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('online_sales', function (Blueprint $table) {
            DB::statement('ALTER TABLE  online_sales MODIFY sale_date TIMESTAMP NOT NULL ');
        });
    }
}
