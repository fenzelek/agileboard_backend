<?php

use App\Models\Db\ModuleMod;
use App\Models\Db\ModPrice;
use Illuminate\Database\Migrations\Migration;

class AddInvoceRegistryExportToModPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $mod = ModuleMod::where('value', 'optima')->first();
        ModPrice::create([
            'module_mod_id' => $mod->id,
            'days' => 30,
            'default' => false,
            'price' => 122,
            'vat' => 23,
            'currency' => 'PLN',
        ]);
        ModPrice::create([
            'module_mod_id' => $mod->id,
            'days' => 365,
            'default' => false,
            'price' => 122,
            'vat' => 23,
            'currency' => 'PLN',
        ]);

        $mod = ModuleMod::where('value', 'firmen')->first();
        ModPrice::create([
            'module_mod_id' => $mod->id,
            'days' => 30,
            'default' => false,
            'price' => 122,
            'vat' => 23,
            'currency' => 'PLN',
        ]);
        ModPrice::create([
            'module_mod_id' => $mod->id,
            'days' => 365,
            'default' => false,
            'price' => 122,
            'vat' => 23,
            'currency' => 'PLN',
        ]);
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
