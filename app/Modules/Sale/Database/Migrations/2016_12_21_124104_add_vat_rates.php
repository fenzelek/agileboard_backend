<?php

use App\Models\Db\VatRate;
use Illuminate\Database\Migrations\Migration;

class AddVatRates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $vat_rates_data = [
            [
                'rate' => '23',
                'name' => '23%',
                'is_visible' => true,
            ],
            [
                'rate' => '8',
                'name' => '8%',
                'is_visible' => true,
            ],
            [
                'rate' => '5',
                'name' => '5%',
                'is_visible' => true,
            ],
            [
                'rate' => '0',
                'name' => '0%',
                'is_visible' => true,
            ],
            [
                'rate' => '0',
                'name' => '0% WDT',
                'is_visible' => true,
            ],
            [
                'rate' => '0',
                'name' => '0% EXP',
                'is_visible' => true,
            ],
            [
                'rate' => '0',
                'name' => 'np. UE',
                'is_visible' => true,
            ],
            [
                'rate' => '0',
                'name' => 'np.',
                'is_visible' => true,
            ],
            [
                'rate' => '0',
                'name' => 'zw.',
                'is_visible' => true,
            ],
            [
                'rate' => '22',
                'name' => '22%',
                'is_visible' => true,
            ],
            [
                'rate' => '7',
                'name' => '7%',
                'is_visible' => true,
            ],
            [
                'rate' => '3',
                'name' => '3%',
                'is_visible' => true,
            ],
        ];

        foreach ($vat_rates_data as $item) {
            $vat_rate = new VatRate();
            $vat_rate->rate = $item['rate'];
            $vat_rate->name = $item['name'];
            $vat_rate->visible = $item['is_visible'];
            $vat_rate->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        VatRate::truncate();
    }
}
