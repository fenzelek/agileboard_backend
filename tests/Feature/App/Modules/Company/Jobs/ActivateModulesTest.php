<?php

namespace Tests\Feature\App\Modules\Company\Jobs;

use App\Models\Db\CompanyModule;
use App\Models\Db\ModPrice;
use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Models\Db\Transaction;
use App\Models\Other\RoleType;
use App\Modules\Company\Jobs\ActivateModules;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Company\Services\Payments\ModuleService;
use App\Models\Db\CompanyModuleHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;

class ActivateModulesTest extends TestCase
{
    use DatabaseTransactions, ExtendModule;

    private $job;

    protected function setUp():void
    {
        parent::setUp();

        $this->job = new ActivateModules(new CompanyModuleUpdater(), new ModuleService());
        $this->createTestExtendModule();
    }

    /** @test */
    public function handle_externaModule_success()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->first();

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $history = $this->createHistory($company, $mod, null, Carbon::now()->subDay(), Carbon::now()->addDay());

        $count_companyModules = CompanyModule::count();

        $this->job->handle();

        $this->assertSame($count_companyModules, CompanyModule::count());

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $mod->module_id)->first();
        $this->assertSame($mod->value, $company_module->value);
        $this->assertSame($history->expiration_date->toDateTimeString(), $company_module->expiration_date->toDateTimeString());
        $this->assertSame(null, $company_module->package_id);
        $this->assertSame($history->subscription_id, $company_module->subscription_id);

        $this->assertNull($company->fresh()->blockade_company);
    }

    /** @test */
    public function handle_externaModule_other_status()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();

        $module = Module::findBySlug('test.extend.module');
        $mod_old = $module->mods()->where('value', '')->first();
        $mod = $module->mods()->where('value', 'test1')->first();

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $history = $this->createHistory($company, $mod, null, Carbon::now()->subDay(), Carbon::now()->addDay(), CompanyModuleHistory::STATUS_USED);

        $count_companyModules = CompanyModule::count();

        $this->job->handle();

        $this->assertSame($count_companyModules, CompanyModule::count());

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $mod->module_id)->first();
        $this->assertSame($mod_old->value, $company_module->value);
        $this->assertSame(null, $company_module->expiration_date);
        $this->assertSame(null, $company_module->package_id);
        $this->assertSame($history->subscription_id, $company_module->subscription_id);
    }

    /** @test */
    public function handle_externaModule_other_before_start()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();

        $module = Module::findBySlug('test.extend.module');
        $mod_old = $module->mods()->where('value', '')->first();
        $mod = $module->mods()->where('value', 'test1')->first();

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $history = $this->createHistory($company, $mod, null, Carbon::now()->addDay(), Carbon::now()->addDays(2));

        $count_companyModules = CompanyModule::count();

        $this->job->handle();

        $this->assertSame($count_companyModules, CompanyModule::count());

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $mod->module_id)->first();
        $this->assertSame($mod_old->value, $company_module->value);
        $this->assertSame(null, $company_module->expiration_date);
        $this->assertSame(null, $company_module->package_id);
        $this->assertSame($history->subscription_id, $company_module->subscription_id);
    }

    /** @test */
    public function handle_externaModule_other_after_start()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();

        $module = Module::findBySlug('test.extend.module');
        $mod_old = $module->mods()->where('value', '')->first();
        $mod = $module->mods()->where('value', 'test1')->first();

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $history = $this->createHistory($company, $mod, null, Carbon::now()->subDays(2), Carbon::now()->subDay());

        $count_companyModules = CompanyModule::count();

        $this->job->handle();

        $this->assertSame($count_companyModules, CompanyModule::count());

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $mod->module_id)->first();
        $this->assertSame($mod_old->value, $company_module->value);
        $this->assertSame(null, $company_module->expiration_date);
        $this->assertSame(null, $company_module->package_id);
        $this->assertSame($history->subscription_id, $company_module->subscription_id);
    }

    /** @test */
    public function handle_package_success()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);

        $package_id = Package::where('slug', Package::PREMIUM)->first()->id;

        $prices = ModPrice::where(function ($q) use ($package_id) {
            $q->where('package_id', $package_id);
            $q->where('default', 1);
        })->with('moduleMod.module')->get();

        $transaction = factory(Transaction::class)->create();

        $history = [];
        foreach ($prices as $price) {
            $history [] = $this->createHistory(
                $company,
                $price->moduleMod,
                $package_id,
                Carbon::now()->subDay(),
                Carbon::now()->addDay(),
                CompanyModuleHistory::STATUS_NOT_USED,
                $transaction->id
            );
        }

        $count_companyModules = CompanyModule::count();

        $this->job->handle();

        $this->assertSame($count_companyModules, CompanyModule::count());

        foreach ($history as $item) {
            $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $item->module_id)->first();
            $this->assertSame($item->new_value, $company_module->value);
            $this->assertSame($item->package_id, $company_module->package_id);
            $this->assertSame($item->expiration_date->toDateTimeString(), $company_module->expiration_date->toDateTimeString());
            $this->assertSame($item->subscription_id, $company_module->subscription_id);
        }

        $this->assertNull($company->fresh()->blockade_company);
    }

    private function createHistory(
        $company,
        $module_mod,
        $package_id = null,
        $start_date = null,
        $expiration_date = null,
        $status = CompanyModuleHistory::STATUS_NOT_USED,
        $transaction_id = null
    ) {
        return factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'module_id' => $module_mod->module_id,
            'module_mod_id' => $module_mod->id,
            'new_value' => $module_mod->value,
            'package_id' => $package_id,
            'start_date' => $start_date,
            'expiration_date' => $expiration_date,
            'status' => $status,
            'transaction_id' => $transaction_id ?: factory(Transaction::class)->create()->id,
        ]);
    }
}
