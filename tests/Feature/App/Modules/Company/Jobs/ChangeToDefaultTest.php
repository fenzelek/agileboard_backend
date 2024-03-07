<?php

namespace Tests\Feature\App\Modules\Company\Jobs;

use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Models\Db\Project;
use App\Models\Db\Transaction;
use App\Models\Other\ModuleType;
use App\Models\Other\RoleType;
use App\Modules\Company\Jobs\ChangeToDefault;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Company\Services\Payments\ModuleService;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Payment;
use App\Models\Db\CompanyModule;
use App\Models\Db\Subscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;

class ChangeToDefaultTest extends TestCase
{
    use DatabaseTransactions, ExtendModule;

    private $job;

    protected function setUp():void
    {
        parent::setUp();

        $this->job = new ChangeToDefault(new CompanyModuleUpdater(), new ModuleService());
    }

    /** @test */
    public function handle_externaModuleWithSubscription()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $this->createTestExtendModule();

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', '')->first();

        $company_subscription_enabled = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $subscription_enabled = factory(Subscription::class)->create();
        CompanyModule::where('company_id', $company_subscription_enabled->id)
            ->where('module_id', $module->id)
            ->update([
                'expiration_date' => Carbon::now()->subDay(),
                'subscription_id' => $subscription_enabled->id,
                'value' => 'test1',
            ]);

        $company_subscription_disabled = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $subscription_disabled = factory(Subscription::class)->create(['active' => false]);
        CompanyModule::where('company_id', $company_subscription_disabled->id)
            ->where('module_id', $module->id)
            ->update([
                'expiration_date' => Carbon::now()->subDay(),
                'subscription_id' => $subscription_disabled->id,
                'value' => 'test1',
            ]);

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $this->job->handle();

        $this->assertSame($count_payments, Payment::count());
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - 1);

        $transaction = Transaction::orderByDesc('id')->first();

        $company_module = CompanyModule::orderByDesc('id')->first();
        $this->assertSame($company_subscription_disabled->id, $company_module->company_id);
        $this->assertSame($module->id, $company_module->module_id);
        $this->assertSame('', $company_module->value);
        $this->assertSame(null, $company_module->package_id);
        $this->assertSame(null, $company_module->expiration_date);
        $this->assertSame(null, $company_module->subscription_id);

        $history = CompanyModuleHistory::where('transaction_id', $transaction->id)->first();
        $this->assertSame($company_subscription_disabled->id, $history->company_id);
        $this->assertSame($module->id, $history->module_id);
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('test1', $history->old_value);
        $this->assertSame('', $history->new_value);
        $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame(0, $history->price);
        $this->assertSame('PLN', $history->currency);

        $this->assertNull($history->company->blockade_company);
    }

    /** @test */
    public function handle_externaModuleWithoutSubscription()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $this->createTestExtendModule();

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', '')->first();

        $company_with_default = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);

        $company_other = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        CompanyModule::where('company_id', $company_other->id)
            ->where('module_id', $module->id)
            ->update([
                'expiration_date' => Carbon::now()->addDay(),
                'subscription_id' => null,
                'value' => 'test1',
            ]);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        CompanyModule::where('company_id', $company->id)
            ->where('module_id', $module->id)
            ->update([
                'expiration_date' => Carbon::now()->subDay(),
                'subscription_id' => null,
                'value' => 'test1',
            ]);

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $this->job->handle();

        $this->assertSame($count_payments, Payment::count());
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - 1);

        $transaction = Transaction::orderByDesc('id')->first();

        $company_module = CompanyModule::orderByDesc('id')->first();
        $this->assertSame($company->id, $company_module->company_id);
        $this->assertSame($module->id, $company_module->module_id);
        $this->assertSame('', $company_module->value);
        $this->assertSame(null, $company_module->package_id);
        $this->assertSame(null, $company_module->expiration_date);
        $this->assertSame(null, $company_module->subscription_id);

        $history = CompanyModuleHistory::where('transaction_id', $transaction->id)->first();
        $this->assertSame($company->id, $history->company_id);
        $this->assertSame($module->id, $history->module_id);
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('test1', $history->old_value);
        $this->assertSame('', $history->new_value);
        $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame(0, $history->price);
        $this->assertSame('PLN', $history->currency);

        $this->assertNull($history->company->blockade_company);
    }

    /** @test */
    public function handle_packageWithSubscription()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $this->createTestExtendModule();

        $company_subscription_enabled = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->subDay());
        $subscription_enabled = factory(Subscription::class)->create();
        CompanyModule::where('company_id', $company_subscription_enabled->id)
            ->whereNotNull('package_id')
            ->update(['subscription_id' => $subscription_enabled->id]);

        $company_subscription_disabled = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->subDay());
        $subscription_disabled = factory(Subscription::class)->create(['active' => false]);
        CompanyModule::where('company_id', $company_subscription_disabled->id)
            ->whereNotNull('package_id')
            ->update(['subscription_id' => $subscription_disabled->id]);

        $count_company_modules_in_company = CompanyModule::where('company_id', $company_subscription_disabled->id)
            ->whereNotNull('package_id')->count();

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $this->job->handle();

        $this->assertSame($count_payments, Payment::count());
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - $count_company_modules_in_company);

        $transaction = Transaction::orderByDesc('id')->first();

        $package_free = Package::findDefault();

        foreach (CompanyModule::where('company_id', $company_subscription_disabled->id)->whereNotNull('package_id')->get() as $company_module) {
            $module = $package_free->modules()->where('id', $company_module->module_id)->with(['mods' => function ($q) use ($package_free) {
                $q->whereHas('modPrices', function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                });
                $q->with(['modPrices' => function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                }]);
            }])->first();

            $this->assertTrue((bool) $module);
            $this->assertSame($company_subscription_disabled->id, $company_module->company_id);
            $this->assertSame($module->mods[0]->value, $company_module->value);
            $this->assertSame($module->package_id, $company_module->laravel_through_key);
            $this->assertSame(null, $company_module->expiration_date);
            $this->assertSame(null, $company_module->subscription_id);
        }

        foreach (CompanyModuleHistory::where('transaction_id', $transaction->id)->get() as $history) {
            $module = $package_free->modules()->where('id', $history->module_id)->with(['mods' => function ($q) use ($package_free) {
                $q->whereHas('modPrices', function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                });
                $q->with(['modPrices' => function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                }]);
            }])->first();

            $this->assertSame($company_subscription_disabled->id, $history->company_id);
            $this->assertSame($module->id, $history->module_id);
            $this->assertSame($module->mods[0]->id, $history->module_mod_id);
            $this->assertSame($module->mods[0]->value, $history->new_value);
            $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
            $this->assertSame(null, $history->expiration_date);
            $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
            $this->assertSame($package_free->id, $history->package_id);
            $this->assertSame($module->mods[0]->modPrices[0]->price, $history->price);
            $this->assertSame($module->mods[0]->modPrices[0]->currency, $history->currency);
            $this->assertSame($module->mods[0]->modPrices[0]->vat, $history->vat);
        }

        $this->assertNull($company_subscription_disabled->fresh()->blockade_company);
    }

    /** @test */
    public function handle_packageWithoutSubscription()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $this->createTestExtendModule();

        //free package
        $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);

        //company not for use
        $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDay());

        //company who has expired package
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->subDay());

        $count_company_modules_in_company = CompanyModule::where('company_id', $company->id)
            ->whereNotNull('package_id')->count();

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $this->job->handle();

        $this->assertSame($count_payments, Payment::count());
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - $count_company_modules_in_company);

        $transaction = Transaction::orderByDesc('id')->first();

        $package_free = Package::findDefault();

        foreach (CompanyModule::where('company_id', $company->id)->whereNotNull('package_id')->get() as $company_module) {
            $module = $package_free->modules()->where('id', $company_module->module_id)->with(['mods' => function ($q) use ($package_free) {
                $q->whereHas('modPrices', function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                });
                $q->with(['modPrices' => function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                }]);
            }])->first();

            $this->assertTrue((bool) $module);
            $this->assertSame($company->id, $company_module->company_id);
            $this->assertSame($module->mods[0]->value, $company_module->value);
            $this->assertSame($module->package_id, $company_module->laravel_through_key);
            $this->assertSame(null, $company_module->expiration_date);
            $this->assertSame(null, $company_module->subscription_id);
        }

        foreach (CompanyModuleHistory::where('transaction_id', $transaction->id)->get() as $history) {
            $module = $package_free->modules()->where('id', $history->module_id)->with(['mods' => function ($q) use ($package_free) {
                $q->whereHas('modPrices', function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                });
                $q->with(['modPrices' => function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                }]);
            }])->first();

            $this->assertSame($company->id, $history->company_id);
            $this->assertSame($module->id, $history->module_id);
            $this->assertSame($module->mods[0]->id, $history->module_mod_id);
            $this->assertSame($module->mods[0]->value, $history->new_value);
            $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
            $this->assertSame(null, $history->expiration_date);
            $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
            $this->assertSame($package_free->id, $history->package_id);
            $this->assertSame($module->mods[0]->modPrices[0]->price, $history->price);
            $this->assertSame($module->mods[0]->modPrices[0]->currency, $history->currency);
            $this->assertSame($module->mods[0]->modPrices[0]->vat, $history->vat);
        }

        $this->assertNull($company->fresh()->blockade_company);
    }

    /** @test */
    public function handle_packageSetBlockadeCompany()
    {
        config()->set('app_settings.package_portal_name', 'ab');

        Package::where('default', 1)->update(['default' => 0]);
        Package::where('slug', Package::CEP_FREE)->update(['default' => 1]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();

        //company who has expired package
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_CLASSIC, Carbon::now()->subDay());

        $projects = factory(Project::class, 8)->create(['company_id' => $company->id]);

        $count_company_modules_in_company = CompanyModule::where('company_id', $company->id)
            ->whereNotNull('package_id')->count();

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $this->job->handle();

        $this->assertSame($count_payments, Payment::count());
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - $count_company_modules_in_company);

        $transaction = Transaction::orderByDesc('id')->first();

        $package_free = Package::findDefault();

        foreach (CompanyModule::where('company_id', $company->id)->whereNotNull('package_id')->get() as $company_module) {
            $module = $package_free->modules()->where('id', $company_module->module_id)->with(['mods' => function ($q) use ($package_free) {
                $q->whereHas('modPrices', function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                });
                $q->with(['modPrices' => function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                }]);
            }])->first();

            $this->assertTrue((bool) $module);
            $this->assertSame($company->id, $company_module->company_id);
            $this->assertSame($module->mods[0]->value, $company_module->value);
            $this->assertSame($module->package_id, $company_module->laravel_through_key);
            $this->assertSame(null, $company_module->expiration_date);
            $this->assertSame(null, $company_module->subscription_id);
        }

        foreach (CompanyModuleHistory::where('transaction_id', $transaction->id)->get() as $history) {
            $module = $package_free->modules()->where('id', $history->module_id)->with(['mods' => function ($q) use ($package_free) {
                $q->whereHas('modPrices', function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                });
                $q->with(['modPrices' => function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                }]);
            }])->first();

            $this->assertSame($company->id, $history->company_id);
            $this->assertSame($module->id, $history->module_id);
            $this->assertSame($module->mods[0]->id, $history->module_mod_id);
            $this->assertSame($module->mods[0]->value, $history->new_value);
            $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
            $this->assertSame(null, $history->expiration_date);
            $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
            $this->assertSame($package_free->id, $history->package_id);
            $this->assertSame($module->mods[0]->modPrices[0]->price, $history->price);
            $this->assertSame($module->mods[0]->modPrices[0]->currency, $history->currency);
            $this->assertSame($module->mods[0]->modPrices[0]->vat, $history->vat);
        }

        $this->assertSame(ModuleType::PROJECTS_MULTIPLE_PROJECTS, $company->fresh()->blockade_company);
    }
}
