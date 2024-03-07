<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\ModPrice;
use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Models\Db\PackageModule;
use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\ModuleMod;

class MoveRegistryExportModuleToPackage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = Module::where('slug', 'invoices.registry.export.name')->first();

        $package_free = Package::findBySlug(Package::START);
        $package_premium = Package::findBySlug(Package::PREMIUM);

        PackageModule::create(['package_id' => $package_free->id, 'module_id' => $module->id]);
        PackageModule::create(['package_id' => $package_premium->id, 'module_id' => $module->id]);

        ModPrice::whereNull('package_id')->whereNull('days')->update(['package_id' => $package_premium->id, 'days' => 30]);
        ModPrice::whereNull('package_id')->update(['package_id' => $package_premium->id]);
        ModPrice::where('days', 30)->where('price', 122)->update(['price' => 123]);
        ModPrice::where('days', 365)->where('price', 122)->update(['price' => 0]);

        $mod = $module->mods()->where('value', '')->first();
        ModPrice::create(['package_id' => $package_premium->id, 'module_mod_id' => $mod->id, 'default' => '0', 'price' => 0, 'days' => 365]);
        ModPrice::create(['package_id' => $package_free->id, 'module_mod_id' => $mod->id, 'default' => '1', 'price' => 0]);
        ModPrice::create(['package_id' => Package::findBySlug(Package::ICONTROL)->id, 'module_mod_id' => $mod->id, 'default' => '1', 'price' => 0]);
        ModPrice::create(['package_id' => Package::findBySlug(Package::CEP_FREE)->id, 'module_mod_id' => $mod->id, 'default' => '1', 'price' => 0]);

        $mod_test = ModuleMod::create(['module_id' => $module->id, 'test' => 1, 'value' => 'optima']);
        ModPrice::create(['package_id' => $package_premium->id, 'module_mod_id' => $mod_test->id, 'default' => '0', 'price' => 0, 'days' => 30]);

        $modules = CompanyModule::whereNull('package_id')->get();
        foreach ($modules as $item) {
            $company_module = CompanyModule::where('company_id', $item->company_id)->whereNotNull('package_id')->first();
            CompanyModule::where('id', $item->id)->update(['package_id' => $company_module->package_id, 'expiration_date' => $company_module->expiration_date]);
        }

        CompanyModuleHistory::whereNull('package_id')->delete();
        $history = CompanyModuleHistory::whereNotNull('package_id')->groupBy('company_id')->get();

        foreach ($history as $item) {
            $h = CompanyModuleHistory::where('company_id', $item->company_id)->orderBy('id', 'desc')->first();

            $company_module = CompanyModule::where('module_id', $module->id)->where('company_id', $item->company_id)->first();

            $mod_id = $mod->id;

            if ($company_module) {
                if ($company_module->value != '') {
                    $mod_id = ModuleMod::where('module_id', $module->id)->where('value', $company_module->value)
                        ->where('test', 0)->first()->id;
                }

                CompanyModuleHistory::create([
                    'company_id' => $h->company_id,
                    'module_id' => $module->id,
                    'module_mod_id' => $mod_id,
                    'package_id' => $package_premium->id,
                    'transaction_id' => $h->transaction_id,
                    'old_value' => null,
                    'new_value' => $company_module->value,
                    'start_date' => $h->start_date,
                    'expiration_date' => $h->expiration_date,
                    'status' => 1,
                    'currency' => 'PLN',
                ]);
            }
        }

        ModPrice::where('price', 990)->update(['price' => 1218]);
        ModPrice::where('price', 10000)->update(['price' => 14613]);

        ModPrice::where('currency', '')->update(['currency' => 'PLN']);

        CompanyModuleHistory::where('currency', '')->update(['currency' => 'PLN', 'status' => 1]);
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
