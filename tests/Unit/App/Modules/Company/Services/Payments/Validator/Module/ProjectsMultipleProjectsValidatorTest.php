<?php

namespace Tests\Unit\App\Modules\Company\Services\Payments\Validator\Module;

use App\Models\Db\Company;
use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;
use App\Models\Db\Package;
use App\Models\Db\Payment;
use App\Models\Db\Project;
use App\Models\Other\PaymentStatus;
use App\Modules\Company\Services\Payments\Validator\Module\ProjectsMultipleProjectsValidator;
use App\Modules\Company\Services\Payments\Validator\ValidatorErrors;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Company\Services\Payments\Validator\Module\Traits\PackageModuleValidateTrait;

class ProjectsMultipleProjectsValidatorTest extends TestCase
{
    use DatabaseTransactions, PackageModuleValidateTrait;

    public $validator;

    public function setUp():void
    {
        parent::setUp();
        $this->validator = new ProjectsMultipleProjectsValidator();
    }

    /** @test */
    public function canUpdateCompanyModule_unlimited()
    {
        $mod = factory(ModuleMod::class)->create(['value' => ModuleMod::UNLIMITED]);
        $this->assertTrue($this->validator->canUpdateCompanyModule($mod));
    }

    /** @test */
    public function canUpdateCompanyModule_equalProjects()
    {
        $company = factory(Company::class)->create();
        factory(Project::class, 2)->create(['company_id' => $company->id]);

        $mod = factory(ModuleMod::class)->create(['value' => 2]);

        $this->validator->setCompany($company);
        $this->assertTrue($this->validator->canUpdateCompanyModule($mod));
    }

    /** @test */
    public function canUpdateCompanyModule_moreProjects()
    {
        $company = factory(Company::class)->create();
        factory(Project::class, 2)->create(['company_id' => $company->id]);

        $mod = factory(ModuleMod::class)->create(['value' => 1]);

        $this->validator->setCompany($company);
        $this->assertFalse($this->validator->canUpdateCompanyModule($mod));
    }

    /** @test */
    public function canUpdateCompanyModule_lessProjects()
    {
        $company = factory(Company::class)->create();
        factory(Project::class, 2)->create(['company_id' => $company->id]);

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

        factory(Project::class, 2)->create(['company_id' => $company->id]);

        $this->validator->setCompany($company);
        $this->assertSame(ValidatorErrors::UNAVAILABLE_VALUE, $this->validator->validate($mod_test));
    }
}
