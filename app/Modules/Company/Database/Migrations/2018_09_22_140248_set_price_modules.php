<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\Package;
use App\Models\Db\ModPrice;
use App\Models\Db\Module;

class SetPriceModules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ModPrice::where('price', 0)->update(['vat' => 0]);

        $package = Package::findBySlug(Package::PREMIUM);
        $mod = Module::findBySlug('invoices.proforma.enabled')->mods()->where('value', 1)->where('test', 0)->first();
        ModPrice::where('package_id', $package->id)->where('module_mod_id', $mod->id)->where('days', 30)
            ->update(['price' => 990, 'vat' => 187]);
        ModPrice::where('package_id', $package->id)->where('module_mod_id', $mod->id)->where('days', 365)
            ->update(['price' => 10000, 'vat' => 1870]);
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
