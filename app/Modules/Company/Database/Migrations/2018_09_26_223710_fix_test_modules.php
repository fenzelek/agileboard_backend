<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;
use App\Models\Db\ModPrice;
use App\Models\Db\Package;

class FixTestModules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $premium = Package::findBySlug(Package::PREMIUM);

        $module = Module::where('slug', 'receipts.active')->first();
        ModuleMod::where('module_id', $module->id)->where('test', '1')->update(['value' => 0]);
        $mod = ModuleMod::where('module_id', $module->id)->where('test', '1')->first();
        ModPrice::create([
            'module_mod_id' => $mod->id,
            'package_id' => $premium->id,
            'days' => 30,
            'default' => 0,
            'price' => 0,
            'currency' => 'PLN',
        ]);

        $module = Module::where('slug', 'general.welcome_url')->first();
        $mod = ModuleMod::create([
            'module_id' => $module->id,
            'test' => 1,
            'value' => 'app.dashboard',
        ]);

        ModPrice::create([
            'module_mod_id' => $mod->id,
            'package_id' => $premium->id,
            'days' => 30,
            'default' => 0,
            'price' => 0,
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
