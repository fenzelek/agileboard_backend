<?php

namespace Tests\Unit\App\Modules\Company\Services\Payments\Validator\Module\Traits;

use App\Models\Db\Company;
use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;
use App\Models\Db\Package;
use App\Models\Db\Payment;
use App\Models\Other\PaymentStatus;
use App\Modules\Company\Services\Payments\Validator\ValidatorErrors;
use Carbon\Carbon;

trait PackageModuleValidateTrait
{
    /** @test */
    public function validate_testAndSomeTestWasUsed()
    {
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod_test_1 = factory(ModuleMod::class)->create(['module_id' => $module->id, 'test' => true, 'value' => '1']);
        $mod_test_2 = factory(ModuleMod::class)->create(['module_id' => $module->id, 'test' => true, 'value' => '1']);
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id]);
        $history = factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'module_mod_id' => $mod_test_1->id,
        ]);

        $this->validator->setCompany($company);
        $this->assertFalse($this->validator->validate($mod_test_2));
    }

    /** @test */
    public function validate_testAndIsActivePremium()
    {
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod_test = factory(ModuleMod::class)->create(['module_id' => $module->id, 'test' => true, 'value' => '1']);
        $mod_premium = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);
        $companyModule = factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'value' => '1',
        ]);

        $this->validator->setCompany($company);
        $this->assertFalse($this->validator->validate($mod_test));
    }

    /** @test */
    public function validate_isNowUsedCanExtend()
    {
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        factory(ModuleMod::class)->create(['module_id' => $module->id]);
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => 'asd']);
        $companyModule = factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'value' => $mod->value,
            'expiration_date' => Carbon::now()->addDays(20),
        ]);

        $this->validator->setCompany($company);
        $this->assertSame(ValidatorErrors::MODULE_MOD_CURRENTLY_USED_CAN_EXTEND, $this->validator->validate($mod));
    }

    /** @test */
    public function validate_isNowUsedMoreThenMonthForEnd()
    {
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        factory(ModuleMod::class)->create(['module_id' => $module->id]);
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => 'asd']);
        $companyModule = factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'value' => $mod->value,
            'expiration_date' => Carbon::now()->addDays(60),
        ]);

        $this->validator->setCompany($company);
        $this->assertSame(ValidatorErrors::MODULE_MOD_CURRENTLY_USED, $this->validator->validate($mod));
    }

    /** @test */
    public function validate_isNowUsedWithSubscription()
    {
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        factory(ModuleMod::class)->create(['module_id' => $module->id]);
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => 'asd']);
        $companyModule = factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'value' => $mod->value,
            'expiration_date' => Carbon::now()->addDays(20),
            'subscription_id' => 123,
        ]);

        $this->validator->setCompany($company);
        $this->assertSame(ValidatorErrors::MODULE_MOD_CURRENTLY_USED, $this->validator->validate($mod));
    }

    /** @test */
    public function validate_waitingForPayment()
    {
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod_free = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '0']);
        $mod_test = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);

        $module_2 = factory(Module::class)->create();
        $mod_2 = factory(ModuleMod::class)->create(['module_id' => $module_2->id, 'value' => '1']);

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => null,
            'type' => null,
            'subscription_id' => null,
        ]);

        $package_id = Package::where('slug', Package::PREMIUM)->first()->id;
        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module_2->id,
            'package_id' => $package_id,
            'value' => '1',
            'expiration_date' => Carbon::now()->addDays(10),
        ]);
        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'value' => '0',
        ]);
        factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'module_mod_id' => $mod_free->id,
            'transaction_id' => $payment->transaction->id,
        ]);

        $this->validator->setCompany($company);
        $this->assertSame(ValidatorErrors::WAITING_FOR_PAYMENT, $this->validator->validate($mod_test));
    }

    /** @test */
    public function validate_ok()
    {
        $company = factory(Company::class)->create();
        $module = factory(Module::class)->create();
        $mod_free = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '0']);
        $mod_test = factory(ModuleMod::class)->create(['module_id' => $module->id, 'value' => '1']);

        $module_2 = factory(Module::class)->create();
        $mod_2 = factory(ModuleMod::class)->create(['module_id' => $module_2->id, 'value' => '1']);

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_COMPLETED,
            'external_order_id' => null,
            'type' => null,
            'subscription_id' => null,
        ]);

        $package_id = Package::where('slug', Package::PREMIUM)->first()->id;
        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module_2->id,
            'package_id' => $package_id,
            'value' => '1',
            'expiration_date' => Carbon::now()->addDays(10),
        ]);
        factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'value' => '0',
        ]);
        factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'module_mod_id' => $mod_free->id,
            'transaction_id' => $payment->transaction->id,
        ]);

        $this->validator->setCompany($company);
        $this->assertTrue($this->validator->validate($mod_test));
    }
}
