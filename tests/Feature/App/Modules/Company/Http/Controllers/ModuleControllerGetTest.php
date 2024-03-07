<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\CompanyModule;
use App\Models\Db\File;
use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Models\Db\Project;
use App\Models\Db\Subscription;
use App\Models\Db\User;
use App\Models\Other\ModuleType;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Company\Services\Payments\Validator\ValidatorErrors;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;

class ModuleControllerGetTest extends TestCase
{
    use DatabaseTransactions, ExtendModule;

    protected function setUp():void
    {
        parent::setUp();
        $this->createTestExtendModule();
    }

    /** @test */
    public function current_errorNoPermissionForTaxOffice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::TAX_OFFICE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/current?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function current_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/current?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function current_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/current?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function current_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/current?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function current_success()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            Package::START,
            Carbon::now()->addDay()->addYear()
        );

        auth()->loginUsingId($this->user->id);

        $module = factory(Module::class)->create(['available' => 1]);
        $companyModule = factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'expiration_date' => $now,
        ]);

        $module_def_1 = factory(Module::class)->create(['available' => 1]);
        $companyModuleDef_1 = factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'expiration_date' => $now,
            'value' => '',
        ]);

        $module_def_2 = factory(Module::class)->create(['available' => 1]);
        $companyModuleDef_2 = factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'expiration_date' => $now,
            'value' => '0',
        ]);

        $company_other = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company_other->id,
            'module_id' => $module->id,
            'expiration_date' => $now,
        ]);

        $this->get('modules/current?selected_company_id=' . $company->id)
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => [
                    [
                        'id' => $companyModule->id,
                        'company_id' => $companyModule->company_id,
                        'module_id' => $companyModule->module_id,
                        'value' => $companyModule->value,
                        'package_id' => $companyModule->package_id,
                        'subscription_id' => $companyModule->subscription_id,
                        'has_active_subscription' => false,
                        'expiration_date' => $companyModule->expiration_date->toDateTimeString(),
                        'created_at' => $companyModule->created_at->toDateTimeString(),
                        'updated_at' => $companyModule->updated_at->toDateTimeString(),
                        'module' => [
                            'data' => [
                                'id' => $module->id,
                                'name' => $module->name,
                                'slug' => $module->slug,
                                'description' => $module->description,
                                'visible' => $module->visible,
                                'available' => $module->available,
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function current_success_checkInfoSubscription()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            Package::START,
            Carbon::now()->addDay()->addYear()
        );

        auth()->loginUsingId($this->user->id);

        $subscription = factory(Subscription::class)->create();

        $module = factory(Module::class)->create(['available' => 1]);
        $companyModule = factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'expiration_date' => $now,
            'subscription_id' => $subscription->id,
        ]);

        $module_def_1 = factory(Module::class)->create(['available' => 1]);
        $companyModuleDef_1 = factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'expiration_date' => $now,
            'value' => '',
        ]);

        $module_def_2 = factory(Module::class)->create(['available' => 1]);
        $companyModuleDef_2 = factory(CompanyModule::class)->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'expiration_date' => $now,
            'value' => '0',
        ]);

        $company_other = factory(Company::class)->create();

        factory(CompanyModule::class)->create([
            'company_id' => $company_other->id,
            'module_id' => $module->id,
            'expiration_date' => $now,
        ]);

        $response = $this->get('modules/current?selected_company_id=' . $company->id)
            ->assertStatus(200);

        $this->assertSame(true, $response->json()['data'][0]['has_active_subscription']);
    }

    /** @test */
    public function available_errorNoPermissionForTaxOffice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::TAX_OFFICE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/available?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function available_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/available?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function available_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/available?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function available_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/available?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function available_success_365days()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            Package::PREMIUM,
            Carbon::now()->addDays(365)
        );

        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/available?selected_company_id=' . $company->id)
            ->assertStatus(200)
            ->assertJsonStructure($this->availableStructure());

        $module = Module::findBySlug('test.extend.module');

        $json = $response->json()['data'];

        $this->assertSame(1, count($json));
        $this->assertSame($module->id, $json[0]['id']);
        $this->assertSame(2, count($json[0]['mods']['data']));
        $this->assertSame('test1', $json[0]['mods']['data'][0]['value']);
        $this->assertSame('test2', $json[0]['mods']['data'][1]['value']);
        $this->assertSame(false, $json[0]['mods']['data'][0]['error']);
        $this->assertSame(false, $json[0]['mods']['data'][1]['error']);
        $this->assertSame(2, count($json[0]['mods']['data'][0]['mod_prices']['data']));
        $this->assertSame(2, count($json[0]['mods']['data'][1]['mod_prices']['data']));

        $this->assertSame(122, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(122, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(30, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['days']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['checksum']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['checksum_change']);

        $this->assertSame(122, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price']);
        $this->assertSame(122, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price_change']);
        $this->assertSame(365, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['days']);
        $this->checkChecksum($json[0]['mods']['data'][0]['mod_prices']['data'][1]);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['checksum_change']);

        $this->assertSame(122, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price']);
        $this->assertSame(122, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(30, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['days']);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['checksum']);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['checksum_change']);

        $this->assertSame(122, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price']);
        $this->assertSame(122, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price_change']);
        $this->assertSame(365, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['days']);
        $this->checkChecksum($json[0]['mods']['data'][1]['mod_prices']['data'][1]);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['checksum_change']);
    }

    /** @test */
    public function available_success_20daysWithPremiumModActive()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            Package::PREMIUM,
            Carbon::now()->addDays(20)
        );

        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        CompanyModule::where('module_id', $module->id)
            ->update(['value' => 'test2', 'expiration_date' => Carbon::now()->addDays(20)]);

        $response = $this->get('modules/available?selected_company_id=' . $company->id)
            ->assertStatus(200)
            ->assertJsonStructure($this->availableStructure());

        $json = $response->json()['data'];

        $this->assertSame(1, count($json));
        $this->assertSame($module->id, $json[0]['id']);
        $this->assertSame(2, count($json[0]['mods']['data']));
        $this->assertSame('test1', $json[0]['mods']['data'][0]['value']);
        $this->assertSame('test2', $json[0]['mods']['data'][1]['value']);
        $this->assertSame(false, $json[0]['mods']['data'][0]['error']);
        $this->assertSame(ValidatorErrors::MODULE_MOD_CURRENTLY_USED_CAN_EXTEND, $json[0]['mods']['data'][1]['error']);
        $this->assertSame(2, count($json[0]['mods']['data'][0]['mod_prices']['data']));
        $this->assertSame(2, count($json[0]['mods']['data'][1]['mod_prices']['data']));

        $this->assertSame(81, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(81, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(30, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['days']);
        $this->checkChecksum($json[0]['mods']['data'][0]['mod_prices']['data'][0]);
        $this->checkChecksumChange($json[0]['mods']['data'][0]['mod_prices']['data'][0]);

        $this->assertSame(7, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price']);
        $this->assertSame(7, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price_change']);
        $this->assertSame(365, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['days']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['checksum']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['checksum_change']);

        $this->assertSame(81, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price']);
        $this->assertSame(81, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(30, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['days']);
        $this->checkChecksum($json[0]['mods']['data'][1]['mod_prices']['data'][0]);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['checksum_change']);

        $this->assertSame(7, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price']);
        $this->assertSame(7, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price_change']);
        $this->assertSame(365, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['days']);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['checksum']);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['checksum_change']);
    }

    /** @test */
    public function available_success_freePackage()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            Package::START
        );

        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/available?selected_company_id=' . $company->id)
            ->assertStatus(200)
            ->assertJsonStructure($this->availableStructure());

        $module = Module::findBySlug('test.extend.module');

        $json = $response->json()['data'];

        $this->assertSame(1, count($json));
        $this->assertSame($module->id, $json[0]['id']);
        $this->assertSame(2, count($json[0]['mods']['data']));
        $this->assertSame('test1', $json[0]['mods']['data'][0]['value']);
        $this->assertSame('test2', $json[0]['mods']['data'][1]['value']);
        $this->assertSame(ValidatorErrors::FREE_PACKAGE_NOW_USED, $json[0]['mods']['data'][0]['error']);
        $this->assertSame(ValidatorErrors::FREE_PACKAGE_NOW_USED, $json[0]['mods']['data'][1]['error']);
        $this->assertSame(2, count($json[0]['mods']['data'][0]['mod_prices']['data']));
        $this->assertSame(2, count($json[0]['mods']['data'][1]['mod_prices']['data']));
        $this->assertSame(122, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(122, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price']);
        $this->assertSame(122, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price']);
        $this->assertSame(122, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['checksum']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['checksum']);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['checksum']);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['checksum']);
    }

    /** @test */
    public function limits_errorNoPermissionForTaxOffice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::TAX_OFFICE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/limits?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function limits_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/limits?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function limits_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('modules/limits?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function limits_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_FREE);
        auth()->loginUsingId($this->user->id);

        $this->assignUsersToCompany(factory(User::class, 2)->create(), $company);
        $this->assignUsersToCompany(factory(User::class, 2)->create(), $company, RoleType::DEVELOPER, UserCompanyStatus::DELETED);
        $this->assignUsersToCompany(factory(User::class, 2)->create(), $company, RoleType::DEVELOPER, UserCompanyStatus::REFUSED);
        $this->assignUsersToCompany(factory(User::class, 2)->create(), $company, RoleType::DEVELOPER, UserCompanyStatus::SUSPENDED);

        $projects = factory(Project::class, 2)->create(['company_id' => $company->id]);
        factory(File::class, 3)->create(['project_id' => $projects[0]->id, 'size' => 1024 * 1024 * 1024]);

        $response = $this->get('modules/limits?selected_company_id=' . $company->id)->assertStatus(200);
        $data = $response->json()['data'];

        $this->assertSame(3, $data[ModuleType::PROJECTS_DISC_VOLUME]['current']);
        $this->assertSame('3', $data[ModuleType::PROJECTS_DISC_VOLUME]['max']);
        $this->assertSame(3, $data[ModuleType::GENERAL_MULTIPLE_USERS]['current']);
        $this->assertSame('3', $data[ModuleType::GENERAL_MULTIPLE_USERS]['max']);
        $this->assertSame(2, $data[ModuleType::PROJECTS_MULTIPLE_PROJECTS]['current']);
        $this->assertSame('3', $data[ModuleType::PROJECTS_MULTIPLE_PROJECTS]['max']);
    }

    private function availableStructure()
    {
        return [
            'data' => [
                [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'visible',
                    'available',
                    'mods' => [
                        'data' => [
                            [
                                'id',
                                'module_id',
                                'test',
                                'value',
                                'mod_prices' => [
                                    'data' => [
                                        [
                                            'id',
                                            'module_mod_id',
                                            'package_id',
                                            'days',
                                            'default',
                                            'price',
                                            'price_change',
                                            'currency',
                                            'created_at',
                                            'updated_at',
                                            'checksum',
                                            'checksum_change',
                                        ],
                                        [
                                            'id',
                                            'module_mod_id',
                                            'package_id',
                                            'days',
                                            'default',
                                            'price',
                                            'price_change',
                                            'currency',
                                            'created_at',
                                            'updated_at',
                                            'checksum',
                                            'checksum_change',
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'id',
                                'module_id',
                                'test',
                                'value',
                                'mod_prices' => [
                                    'data' => [
                                        [
                                            'id',
                                            'module_mod_id',
                                            'package_id',
                                            'days',
                                            'default',
                                            'price',
                                            'price_change',
                                            'currency',
                                            'created_at',
                                            'updated_at',
                                            'checksum',
                                            'checksum_change',
                                        ],
                                        [
                                            'id',
                                            'module_mod_id',
                                            'package_id',
                                            'days',
                                            'default',
                                            'price',
                                            'price_change',
                                            'currency',
                                            'created_at',
                                            'updated_at',
                                            'checksum',
                                            'checksum_change',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'exec_time',
        ];
    }

    private function checkChecksum($mod_price)
    {
        $check_data = decrypt($mod_price['checksum']);

        $this->assertSame(Carbon::now()->toDateTimeString(), $check_data['time']);
        $this->assertSame($mod_price['price'], $check_data['price']);
        $this->assertSame($mod_price['id'], $check_data['id']);
    }

    private function checkChecksumChange($mod_price)
    {
        $check_data = decrypt($mod_price['checksum_change']);

        $this->assertSame(Carbon::now()->toDateTimeString(), $check_data['time']);
        $this->assertSame($mod_price['price_change'], $check_data['price']);
        $this->assertSame($mod_price['id'], $check_data['id']);
    }
}
