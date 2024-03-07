<?php

namespace Tests\Unit\App\Modules\Company\Services\Payments\Validator\Module;

use App\Models\Db\Company;
use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;
use App\Models\Db\Package;
use App\Models\Db\Payment;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Db\UserCompany;
use App\Models\Other\PaymentStatus;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Company\Services\Payments\Validator\Module\GeneralMultipleUsersValidator;
use App\Modules\Company\Services\Payments\Validator\ValidatorErrors;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Company\Services\Payments\Validator\Module\Traits\PackageModuleValidateTrait;

class GeneralMultipleUsersValidatorTest extends TestCase
{
    use DatabaseTransactions, PackageModuleValidateTrait;

    public $validator;

    public function setUp():void
    {
        parent::setUp();
        $this->validator = new GeneralMultipleUsersValidator();
    }

    /** @test */
    public function canUpdateCompanyModule_unlimited()
    {
        $mod = factory(ModuleMod::class)->create(['value' => ModuleMod::UNLIMITED]);
        $this->assertTrue($this->validator->canUpdateCompanyModule($mod));
    }

    /** @test */
    public function canUpdateCompanyModule_equalUsersAndCheckStatus()
    {
        $company = factory(Company::class)->create();
        $users = factory(User::class, 3)->create();
        UserCompany::create([
            'user_id' => $users[0]->id,
            'company_id' => $company->id,
            'role_id' => 0,
        ]);
        UserCompany::create([
            'user_id' => $users[1]->id,
            'company_id' => $company->id,
            'role_id' => 0,
        ]);
        UserCompany::create([
            'user_id' => $users[2]->id,
            'company_id' => $company->id,
            'role_id' => 0,
            'status' => UserCompanyStatus::DELETED,
        ]);

        $mod = factory(ModuleMod::class)->create(['value' => 2]);

        $this->validator->setCompany($company);
        $this->assertTrue($this->validator->canUpdateCompanyModule($mod));
    }

    /** @test */
    public function canUpdateCompanyModule_moreUsers()
    {
        $company = factory(Company::class)->create();
        $users = factory(User::class, 2)->create();
        UserCompany::create([
            'user_id' => $users[0]->id,
            'company_id' => $company->id,
            'role_id' => 0,
        ]);
        UserCompany::create([
            'user_id' => $users[1]->id,
            'company_id' => $company->id,
            'role_id' => 0,
        ]);

        $mod = factory(ModuleMod::class)->create(['value' => 1]);

        $this->validator->setCompany($company);
        $this->assertFalse($this->validator->canUpdateCompanyModule($mod));
    }

    /** @test */
    public function canUpdateCompanyModule_lessUsers()
    {
        $company = factory(Company::class)->create();
        $users = factory(User::class, 2)->create();
        UserCompany::create([
            'user_id' => $users[0]->id,
            'company_id' => $company->id,
            'role_id' => 0,
        ]);
        UserCompany::create([
            'user_id' => $users[1]->id,
            'company_id' => $company->id,
            'role_id' => 0,
        ]);

        $mod = factory(ModuleMod::class)->create(['value' => 3]);

        $this->validator->setCompany($company);
        $this->assertTrue($this->validator->canUpdateCompanyModule($mod));
    }

    /** @test */
    public function validate_unavailableValue()
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

        $users = factory(User::class, 2)->create();

        $users->each(function ($user) use ($company) {
            $user->companies()->save(
                $company,
                ['role_id' => Role::findByName(RoleType::DEVELOPER)->id, 'status' => UserCompanyStatus::APPROVED]
            );
        });

        $this->validator->setCompany($company);
        $this->assertSame(ValidatorErrors::UNAVAILABLE_VALUE, $this->validator->validate($mod_test));
    }
}
