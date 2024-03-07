<?php

use Illuminate\Database\Migrations\Migration;

class AddValuesToDefaultColumnInModPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\Db\ModPrice::whereHas('moduleMod', function ($q) {
            $q->where('test', 0);
        })
            ->where('days', 30)
            ->orWhereNull('days')
            ->update(['default' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
