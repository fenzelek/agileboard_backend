<?php

namespace Tests\Unit\App\Modules\Company\Services\Payments\Validator;

use App\Models\Db\Company;
use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\ModPrice;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;
use App\Models\Db\Package;
use App\Models\Db\Subscription;
use App\Modules\Company\Services\Payments\Validator\Module\InvoicesProformaEnabledValidator;
use App\Modules\Company\Services\Payments\Validator\Module\TestExtendModuleValidator;
use App\Modules\Company\Services\Payments\Validator\ValidatorErrors;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleValidatorTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function canChangeNow_hasError()
    {
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id]);

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => 1, //not null
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => null,
        ]);

        $mod->error = ValidatorErrors::FREE_PACKAGE_NOW_USED;

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canChangeNow($mod, $modPrice));
    }

    /** @test */
    public function canChangeNow_hasActiveSubscription()
    {
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id]);
        $subscription = factory(Subscription::class)->create(['active' => 1]);

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => 1, //not null
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => null,
            'subscription_id' => $subscription->id,
        ]);

        $mod->error = false;

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canChangeNow($mod, $modPrice));
    }

    /** @test */
    public function canChangeNow_notInInterval()
    {
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365]);

        $mod->error = null;

        $company = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => 1, //not null
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(10),
        ]);

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canChangeNow($mod, $modPrice));
    }

    /** @test */
    public function canChangeNow_success_simply()
    {
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365]);

        $mod->error = null;

        $company = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => 1, //not null
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(100),
        ]);

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertTrue($validator->canChangeNow($mod, $modPrice));
    }

    /** @test */
    public function canChangeNow_successDisabledSubscription()
    {
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365]);
        $subscription = factory(Subscription::class)->create(['active' => 0]);

        $mod->error = null;

        $company = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => 1, //not null
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(100),
            'subscription_id' => $subscription->id,
        ]);

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertTrue($validator->canChangeNow($mod, $modPrice));
    }

    /** @test */
    public function canChangeNow_hasErrorCurrentUsedExternalModule()
    {
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365, 'package_id' => null]);

        $mod->error = ValidatorErrors::MODULE_MOD_CURRENTLY_USED;

        $company = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => null,
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(100),
        ]);

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canChangeNow($mod, $modPrice));
    }

    /** @test */
    public function canChangeNow_hasErrorCurrentUsedPackageModule_CurrentPackage()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365, 'package_id' => $package->id]);

        $mod->error = ValidatorErrors::MODULE_MOD_CURRENTLY_USED;

        $company = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(100),
        ]);

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canChangeNow($mod, $modPrice));
    }

    /** @test */
    public function canChangeNow_hasErrorCurrentUsedPackageModule_OtherPackage()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $package_other = Package::findBySlug(Package::CEP_FREE);
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365, 'package_id' => $package_other->id]);

        $mod->error = ValidatorErrors::MODULE_MOD_CURRENTLY_USED;

        $company = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(100),
        ]);

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertTrue($validator->canChangeNow($mod, $modPrice));
    }

    /** @test */
    public function canChangeNow_hasErrorCurrentUsedPackageModuleCanExtend_OtherPackage()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $package_other = Package::findBySlug(Package::CEP_FREE);
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365, 'package_id' => $package_other->id]);

        $mod->error = ValidatorErrors::MODULE_MOD_CURRENTLY_USED_CAN_EXTEND;

        $company = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(100),
        ]);

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertTrue($validator->canChangeNow($mod, $modPrice));
    }

    /** @test */
    public function canRenew_hasErrorAny()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365, 'package_id' => $package->id]);

        $mod->error = ValidatorErrors::FREE_PACKAGE_NOW_USED;

        $company = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(100),
        ]);

        $validator = new InvoicesProformaEnabledValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canRenew($mod, $modPrice));
    }

    /** @test */
    public function canRenew_hasErrorButCanExtend()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365, 'package_id' => $package->id]);

        $mod->error = ValidatorErrors::MODULE_MOD_CURRENTLY_USED_CAN_EXTEND;

        $company = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(100),
        ]);

        $validator = new InvoicesProformaEnabledValidator();
        $validator->setCompany($company);
        $this->assertTrue($validator->canRenew($mod, $modPrice));
    }

    /** @test */
    public function canRenew_noErrors_wrongExpirationTime()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365, 'package_id' => $package->id]);

        $mod->error = null;

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => 1, //not null
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(100),
        ]);

        $validator = new InvoicesProformaEnabledValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canRenew($mod, $modPrice));
    }

    /** @test */
    public function canRenew_noErrors_wrongInterval()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365, 'package_id' => $package->id]);

        $mod->error = null;

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(20),
        ]);

        $this->setCurrentPackageAsTest($package, $company);

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canRenew($mod, $modPrice));
    }

    /** @test */
    public function canRenew_noErrors_hasActiveSubscription()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 30, 'package_id' => $package->id]);
        $subscription = factory(Subscription::class)->create(['active' => 1]);

        $mod->error = null;

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => 1, //not null
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(20),
            'subscription_id' => $subscription->id,
        ]);

        $validator = new InvoicesProformaEnabledValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canRenew($mod, $modPrice));
    }

    /** @test */
    public function canRenew_noErrors_hasPendingMod()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 365, 'package_id' => $package->id]);

        $mod->error = null;

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(20),
        ]);

        factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'package_id' => 1, //not null
            'module_id' => $module->id,
            'module_mod_id' => $mod->id,
            'status' => '0',
            'start_date' => Carbon::now()->addDays(20),
            'expiration_date' => Carbon::now()->addDays(100),
        ]);

        $validator = new InvoicesProformaEnabledValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canRenew($mod, $modPrice));
    }

    /** @test */
    public function canRenew_noErrors_success_simply()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 30, 'package_id' => $package->id]);

        $mod->error = null;

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => 1, //not null
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(20),
        ]);

        $validator = new InvoicesProformaEnabledValidator();
        $validator->setCompany($company);
        $this->assertTrue($validator->canRenew($mod, $modPrice));
    }

    /** @test */
    public function canRenew_noErrors_success_hasDisabledSubscription()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 30, 'package_id' => $package->id]);
        $subscription = factory(Subscription::class)->create(['active' => 0]);

        $mod->error = null;

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => 1, //not null
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(20),
            'subscription_id' => $subscription->id,
        ]);

        $validator = new InvoicesProformaEnabledValidator();
        $validator->setCompany($company);
        $this->assertTrue($validator->canRenew($mod, $modPrice));
    }

    /** @test */
    public function canRenew_noErrors_packageTestUsed()
    {
        $package = Package::findBySlug(Package::PREMIUM);
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 30, 'package_id' => $package->id]);

        $mod->error = null;

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'value' => '0',
            'expiration_date' => Carbon::now()->addDays(20),
        ]);

        $this->setCurrentPackageAsTest($package, $company);

        $validator = new TestExtendModuleValidator();
        $validator->setCompany($company);
        $this->assertFalse($validator->canRenew($mod, $modPrice));
    }

    private function setCurrentPackageAsTest($package, $company)
    {
        $module = factory(Module::class)->create();
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1', 'test' => 1]);
        $modPrice = factory(ModPrice::class)->create(['module_mod_id' => $mod->id, 'days' => 30, 'package_id' => $package->id]);

        $expiration_date = Carbon::now()->addDays(30);

        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'module_id' => $module->id,
            'value' => '1',
            'expiration_date' => $expiration_date,
        ]);

        factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'module_id' => $module->id,
            'module_mod_id' => $mod->id,
            'new_value' => '1',
            'expiration_date' => $expiration_date,
        ]);
    }
}
