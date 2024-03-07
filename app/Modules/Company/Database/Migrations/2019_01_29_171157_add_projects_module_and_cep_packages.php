<?php

use App\Models\Db\Package;
use App\Models\Db\Module;
use App\Models\Db\Company;
use App\Models\Db\PackageModule;
use App\Models\Db\ModuleMod;
use App\Models\Db\ModPrice;
use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;

class AddProjectsModuleAndCepPackages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //packages
        $package_classics = Package::create(['slug' => Package::CEP_CLASSIC, 'name' => 'Classic', 'default' => 0, 'portal_name' => 'ab']);
        $package_business = Package::create(['slug' => Package::CEP_BUSINESS, 'name' => 'Business', 'default' => 0, 'portal_name' => 'ab']);
        $package_enterprise = Package::create(['slug' => Package::CEP_ENTERPRISE, 'name' => 'Enterprise', 'default' => 0, 'portal_name' => 'ab_enterprise']);
        Package::where('slug', Package::CEP_FREE)->update(['name' => 'Free']);
        $package_free = Package::findBySlug(Package::CEP_FREE);

        //modules
        Module::where('slug', ModuleType::GENERAL_MULTIPLE_USERS)->update(['name' => 'Numbers of users', 'description' => 'Numbers of users']);

        $module_disc = Module::create(['name' => 'Projects Module - disc volume', 'slug' => ModuleType::PROJECTS_DISC_VOLUME, 'description' => 'Projects Module - disc volume', 'visible' => 1, 'available' => 1]);
        $module_file = Module::create(['name' => 'Projects Module - max file size', 'slug' => ModuleType::PROJECTS_FILE_SIZE, 'description' => 'Projects Module - max file size', 'visible' => 1, 'available' => 1]);
        $module_hubstaff = Module::create(['name' => 'Projects Module - hubstaff integrations', 'slug' => ModuleType::PROJECTS_INTEGRATIONS_HUBSTAFF, 'description' => 'Projects Module - hubstaff integrations', 'visible' => 1, 'available' => 1]);
        $module_projects = Module::create(['name' => 'Projects Module - numbers of projects', 'slug' => ModuleType::PROJECTS_MULTIPLE_PROJECTS, 'description' => 'Projects Module - numbers of projects', 'visible' => 1, 'available' => 1]);
        $module_users_in_project = Module::create(['name' => 'Projects Module - limit users in project', 'slug' => ModuleType::PROJECTS_USERS_IN_PROJECT, 'description' => 'Projects Module - limit users in project', 'visible' => 0, 'available' => 1]);
        $module_users = Module::findBySlug(ModuleType::GENERAL_MULTIPLE_USERS);
        $module_invoices = Module::findBySlug(ModuleType::INVOICES_ACTIVE);

        //package modules
        foreach (PackageModule::where('package_id', $package_free->id)->get() as $pm) {
            PackageModule::create(['package_id' => $package_classics->id, 'module_id' => $pm->module_id]);
            PackageModule::create(['package_id' => $package_business->id, 'module_id' => $pm->module_id]);
            PackageModule::create(['package_id' => $package_enterprise->id, 'module_id' => $pm->module_id]);
        }

        foreach ([$module_disc, $module_file, $module_projects, $module_users_in_project, $module_invoices, $module_hubstaff] as $m) {
            PackageModule::create(['package_id' => $package_classics->id, 'module_id' => $m->id]);
            PackageModule::create(['package_id' => $package_business->id, 'module_id' => $m->id]);
            PackageModule::create(['package_id' => $package_enterprise->id, 'module_id' => $m->id]);
            PackageModule::create(['package_id' => $package_free->id, 'module_id' => $m->id]);
        }

        //module mods
        $mod_prices = ModPrice::where('package_id', $package_free->id)->get();
        foreach ($mod_prices as $mod_price) {
            if ($mod_price->moduleMod->module->slug == ModuleType::INVOICES_REGISTER_EXPORT_NAME) {
                ModPrice::where('id', $mod_price->id)->delete();
                CompanyModule::where('module_id', $mod_price->moduleMod->module->id)->delete();
                CompanyModuleHistory::where('module_id', $mod_price->moduleMod->module->id)->delete();
                continue;
            }
            if ($mod_price->moduleMod->module_id != $module_users->id) {
                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);

                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
                ModPrice::create(['module_mod_id' => $mod_price->module_mod_id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);

                //test
                $mod_test = ModuleMod::create(['module_id' => $mod_price->moduleMod->module_id, 'test' => 1, 'value' => $mod_price->moduleMod->value]);
                ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
                ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
            }
        }

        $this->invoiceMods($module_invoices, $package_free, $package_classics, $package_business, $package_enterprise);
        $this->discMods($module_disc, $package_free, $package_classics, $package_business, $package_enterprise);
        $this->fileMods($module_file, $package_free, $package_classics, $package_business, $package_enterprise);
        $this->projectsMods($module_projects, $package_free, $package_classics, $package_business, $package_enterprise);
        $this->usersInProjectMods($module_users_in_project, $package_free, $package_classics, $package_business, $package_enterprise);
        $this->usersMods($module_users, $package_free, $package_classics, $package_business, $package_enterprise);
        $this->hubstaffMods($module_hubstaff, $package_free, $package_classics, $package_business, $package_enterprise);

        $this->updateCompanyModule($module_invoices, $module_disc, $module_file, $module_projects, $module_users_in_project, $module_users, $module_hubstaff, $package_free);
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

    private function invoiceMods($module, $package_free, $package_classics, $package_business, $package_enterprise)
    {
        $mod = ModuleMod::where('module_id', $module->id)->where('value', '0')->first();
        $mod_test = ModuleMod::create(['module_id' => $module->id, 'test' => 1, 'value' => 0]);

        foreach ([$package_classics, $package_business] as $p) {
            ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $p->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);

            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $p->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $p->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);

            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $p->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $p->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
        }
        foreach ([$package_enterprise, $package_free] as $p) {
            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $p->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $p->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        }
    }

    private function discMods($module, $package_free, $package_classics, $package_business, $package_enterprise)
    {
        $mod_3 = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => 3]);
        $mod_30 = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => 30]);
        $mod_test = ModuleMod::create(['module_id' => $module->id, 'test' => 1, 'value' => 30]);
        $mod_unlimited = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => ModuleMod::UNLIMITED]);

        ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);

        ModPrice::create(['module_mod_id' => $mod_3->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_30->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_30->id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_30->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_30->id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);

        ModPrice::create(['module_mod_id' => $mod_3->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_30->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_30->id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_30->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_30->id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
    }

    private function fileMods($module, $package_free, $package_classics, $package_business, $package_enterprise)
    {
        $mod_10 = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => 10]);
        $mod_300 = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => 300]);
        $mod_test = ModuleMod::create(['module_id' => $module->id, 'test' => 1, 'value' => 300]);
        $mod_unlimited = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => ModuleMod::UNLIMITED]);

        ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);

        ModPrice::create(['module_mod_id' => $mod_10->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_300->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_300->id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_300->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_300->id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);

        ModPrice::create(['module_mod_id' => $mod_10->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_300->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_300->id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_300->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_300->id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
    }

    private function projectsMods($module, $package_free, $package_classics, $package_business, $package_enterprise)
    {
        //test
        $mod_unlimited_test = ModuleMod::create(['module_id' => $module->id, 'test' => 1, 'value' => ModuleMod::UNLIMITED]);
        $mod_2_test = ModuleMod::create(['module_id' => $module->id, 'test' => 1, 'value' => 2]);

        ModPrice::create(['module_mod_id' => $mod_unlimited_test->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_2_test->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);

        //other
        $pln = [2 => 6000, 3 => 9000, 5 => 15000, 10 => 30000, 15 => 40000, 20 => 45000, 30 => 52500, 40 => 65000, 50 => 75000, 75 => 112500, 100 => 150000, 125 => 187500, 150 => 225000, 175 => 265000, 200 => 300000];

        $mod_3 = null;

        foreach ([2, 3, 5, 10, 15, 20, 30, 40, 50, 75, 100, 125, 150, 175, 200] as $item) {
            $mod = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => $item]);
            if ($item == 3) {
                $mod_3 = $mod;
            }

            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => ($item == 2), 'price' => $pln[$item], 'currency' => 'PLN']);
            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => $pln[$item] * 12, 'currency' => 'PLN']);

            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => ($item == 2), 'price' => $item * 800, 'currency' => 'EUR']);
            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => $item * 800 * 12, 'currency' => 'EUR']);
        }

        $mod_unlimited = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => ModuleMod::UNLIMITED]);

        ModPrice::create(['module_mod_id' => $mod_3->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);

        ModPrice::create(['module_mod_id' => $mod_3->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
    }

    private function usersInProjectMods($module, $package_free, $package_classics, $package_business, $package_enterprise)
    {
        $mod_unlimited = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => ModuleMod::UNLIMITED]);
        $mod_test = ModuleMod::create(['module_id' => $module->id, 'test' => 1, 'value' => 50]);

        ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);

        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);

        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
    }

    private function usersMods($module, $package_free, $package_classics, $package_business, $package_enterprise)
    {
        //test
        $mod_10_test = ModuleMod::create(['module_id' => $module->id, 'test' => 1, 'value' => 10]);
        $mod_50_test = ModuleMod::create(['module_id' => $module->id, 'test' => 1, 'value' => 50]);

        ModPrice::create(['module_mod_id' => $mod_10_test->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_50_test->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);

        //other
        ModuleMod::where('module_id', $module->id)->where('value', '1')->update(['value' => ModuleMod::UNLIMITED]);
        ModuleMod::where('module_id', $module->id)->where('value', '0')->update(['value' => 1]);

        $mod_3 = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => 3]);
        $mod_unlimited = ModuleMod::where('module_id', $module->id)->where('value', ModuleMod::UNLIMITED)->where('test', 0)->first();

        ModPrice::where('module_mod_id', $mod_unlimited->id)->where('package_id', $package_free->id)->update(['module_mod_id' => $mod_3->id]);

        $mod_200 = null;

        foreach ([5, 10, 15, 20, 30, 40, 50, 75, 100, 125, 150, 175, 200] as $item) {
            $mod = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => $item]);

            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => ($item == 5), 'price' => $item * 1500, 'currency' => 'PLN']);
            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => $item * 1500 * 12, 'currency' => 'PLN']);

            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => ($item == 5), 'price' => $item * 500, 'currency' => 'EUR']);
            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_classics->id, 'days' => 365, 'default' => 0, 'price' => $item * 500 * 12, 'currency' => 'EUR']);
        }

        foreach ([50, 100, 150, 200] as $item) {
            $mod = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => $item]);

            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => ($item == 50), 'price' => (($item - 50) / 50) * 12500, 'currency' => 'PLN']);
            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => (($item - 50) / 50) * 12500 * 12, 'currency' => 'PLN']);

            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => ($item == 50), 'price' => (($item - 50) / 50) * 4000, 'currency' => 'EUR']);
            ModPrice::create(['module_mod_id' => $mod->id, 'package_id' => $package_business->id, 'days' => 365, 'default' => 0, 'price' => (($item - 50) / 50) * 4000 * 12, 'currency' => 'EUR']);
        }

        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_unlimited->id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_3->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
    }

    private function hubstaffMods($module, $package_free, $package_classics, $package_business, $package_enterprise)
    {
        $mod_0 = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => '0']);
        $mod_1 = ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => '1']);
        $mod_test = ModuleMod::create(['module_id' => $module->id, 'test' => 1, 'value' => 0]);

        ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $package_classics->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_test->id, 'package_id' => $package_business->id, 'days' => 30, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);

        foreach ([$package_classics, $package_business] as $p) {
            ModPrice::create(['module_mod_id' => $mod_0->id, 'package_id' => $p->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
            ModPrice::create(['module_mod_id' => $mod_0->id, 'package_id' => $p->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'PLN']);
            ModPrice::create(['module_mod_id' => $mod_1->id, 'package_id' => $p->id, 'days' => 30, 'default' => 0, 'price' => 1230, 'currency' => 'PLN']);
            ModPrice::create(['module_mod_id' => $mod_1->id, 'package_id' => $p->id, 'days' => 365, 'default' => 0, 'price' => 14760, 'currency' => 'PLN']);

            ModPrice::create(['module_mod_id' => $mod_0->id, 'package_id' => $p->id, 'days' => 30, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
            ModPrice::create(['module_mod_id' => $mod_0->id, 'package_id' => $p->id, 'days' => 365, 'default' => 0, 'price' => 0, 'currency' => 'EUR']);
            ModPrice::create(['module_mod_id' => $mod_1->id, 'package_id' => $p->id, 'days' => 30, 'default' => 0, 'price' => 610, 'currency' => 'EUR']);
            ModPrice::create(['module_mod_id' => $mod_1->id, 'package_id' => $p->id, 'days' => 365, 'default' => 0, 'price' => 7320, 'currency' => 'EUR']);
        }

        ModPrice::create(['module_mod_id' => $mod_0->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['module_mod_id' => $mod_1->id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'PLN']);

        ModPrice::create(['module_mod_id' => $mod_0->id, 'package_id' => $package_free->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
        ModPrice::create(['module_mod_id' => $mod_1->id, 'package_id' => $package_enterprise->id, 'days' => null, 'default' => 1, 'price' => 0, 'currency' => 'EUR']);
    }

    private function updateCompanyModule($module_invoices, $module_disc, $module_file, $module_projects, $module_users_in_project, $module_users, $module_hubstaff, $package_free)
    {
        $mod_users = ModuleMod::where('module_id', $module_users->id)->where('value', 3)->where('test', 0)->first();
        CompanyModule::where('module_id', $module_users->id)->update(['value' => 3]);
        CompanyModuleHistory::where('module_id', $module_users->id)->update(['new_value' => 3, 'module_mod_id' => $mod_users->id]);

        $mod_invoice = ModuleMod::where('module_id', $module_invoices->id)->where('value', '0')->where('test', 0)->first();
        $mod_disc = ModuleMod::where('module_id', $module_disc->id)->where('value', '3')->where('test', 0)->first();
        $mod_file = ModuleMod::where('module_id', $module_file->id)->where('value', '10')->where('test', 0)->first();
        $mod_projects = ModuleMod::where('module_id', $module_projects->id)->where('value', '3')->where('test', 0)->first();
        $mod_hubstaff = ModuleMod::where('module_id', $module_hubstaff->id)->where('value', '0')->where('test', 0)->first();
        $mod_users_in_project = ModuleMod::where('module_id', $module_users_in_project->id)->where('value', ModuleMod::UNLIMITED)->where('test', 0)->first();

        foreach (Company::get() as $company) {
            $history = CompanyModuleHistory::where('company_id', $company->id)->first();
            if (! $history) {
                continue;
            }

            $transaction_id = $history->transaction_id;

            CompanyModule::create(['company_id' => $company->id, 'module_id' => $module_invoices->id, 'package_id' => $package_free->id, 'value' => 0]);
            CompanyModule::create(['company_id' => $company->id, 'module_id' => $module_disc->id, 'package_id' => $package_free->id, 'value' => 3]);
            CompanyModule::create(['company_id' => $company->id, 'module_id' => $module_file->id, 'package_id' => $package_free->id, 'value' => 10]);
            CompanyModule::create(['company_id' => $company->id, 'module_id' => $module_projects->id, 'package_id' => $package_free->id, 'value' => 3]);
            CompanyModule::create(['company_id' => $company->id, 'module_id' => $module_users_in_project->id, 'package_id' => $package_free->id, 'value' => ModuleMod::UNLIMITED]);
            CompanyModule::create(['company_id' => $company->id, 'module_id' => $module_hubstaff->id, 'package_id' => $package_free->id, 'value' => 0]);

            CompanyModuleHistory::create(['company_id' => $company->id, 'module_id' => $module_invoices->id, 'module_mod_id' => $mod_invoice->id,'package_id' => $package_free->id, 'transaction_id' => $transaction_id, 'new_value' => 0, 'status' => 1, 'currency' => 'PLN']);
            CompanyModuleHistory::create(['company_id' => $company->id, 'module_id' => $module_disc->id, 'module_mod_id' => $mod_disc->id,'package_id' => $package_free->id, 'transaction_id' => $transaction_id, 'new_value' => 3, 'status' => 1, 'currency' => 'PLN']);
            CompanyModuleHistory::create(['company_id' => $company->id, 'module_id' => $module_file->id, 'module_mod_id' => $mod_file->id,'package_id' => $package_free->id, 'transaction_id' => $transaction_id, 'new_value' => 10, 'status' => 1, 'currency' => 'PLN']);
            CompanyModuleHistory::create(['company_id' => $company->id, 'module_id' => $module_projects->id, 'module_mod_id' => $mod_projects->id,'package_id' => $package_free->id, 'transaction_id' => $transaction_id, 'new_value' => 3, 'status' => 1, 'currency' => 'PLN']);
            CompanyModuleHistory::create(['company_id' => $company->id, 'module_id' => $module_users_in_project->id, 'module_mod_id' => $mod_users_in_project->id,'package_id' => $package_free->id, 'transaction_id' => $transaction_id, 'new_value' => ModuleMod::UNLIMITED, 'status' => 1, 'currency' => 'PLN']);
            CompanyModuleHistory::create(['company_id' => $company->id, 'module_id' => $module_hubstaff->id, 'module_mod_id' => $mod_hubstaff->id,'package_id' => $package_free->id, 'transaction_id' => $transaction_id, 'new_value' => 0, 'status' => 1, 'currency' => 'PLN']);
        }
    }
}
