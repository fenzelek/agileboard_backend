<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\ModPrice;
use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Models\Db\PackageModule;

class ChangeExternalModulesInModPricesAndPackageModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $package_free = Package::findBySlug(Package::START);

        $module = Module::where('slug', 'invoices.registry.export.name')->first();

        $mod = $module->mods()->where('value', '')->first();
        ModPrice::where('module_mod_id', $mod->id)->where('package_id', $package_free->id)->update(['package_id' => null]);
        ModPrice::where('module_mod_id', $mod->id)->whereNotNull('package_id')->delete();
        PackageModule::where('module_id', $module->id)->delete();
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
