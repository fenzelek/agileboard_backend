<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Models\Db\CompanyModule;
use App\Models\Db\ModPrice;
use App\Models\Db\ModuleMod;
use App\Models\Db\Package;
use App\Models\Db\Module;
use App\Helpers\ErrorCode;
use App\Models\Db\PackageModule;
use App\Models\Db\Subscription;
use App\Models\Other\RoleType;
use App\Modules\Company\Services\Payments\Validator\ValidatorErrors;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;

class PackageControllerGetTest extends TestCase
{
    use DatabaseTransactions, ExtendModule;

    private $company;
    private $payment;

    protected function setUp():void
    {
        parent::setUp();

        $this->createTestExtendModule();
    }

    /** @test */
    public function index_errorNoPermissionFortaxOffice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::TAX_OFFICE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_success()
    {
        config()->set('app_settings.package_portal_name', 'test');
        $packages = factory(Package::class, 2)->create(['portal_name' => 'test']);
        $packages[0]->update(['default' => 1]);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $modules = factory(Module::class, 2)->create(['available' => 1]);
        $module_notAvailable = factory(Module::class)->create(['available' => 0]);
        factory(PackageModule::class)->create(['package_id' => $packages[0]->id, 'module_id' => $modules[0]->id]);
        factory(PackageModule::class)->create(['package_id' => $packages[0]->id, 'module_id' => $modules[1]->id]);
        factory(PackageModule::class)->create(['package_id' => $packages[1]->id, 'module_id' => $modules[0]->id]);
        factory(PackageModule::class)->create(['package_id' => $packages[1]->id, 'module_id' => $module_notAvailable->id]);
        $moduleMod_0 = factory(ModuleMod::class)->create(['module_id' => $modules[0]->id]);
        $moduleMod_1 = factory(ModuleMod::class)->create(['module_id' => $modules[0]->id]);
        $moduleMod_2 = factory(ModuleMod::class)->create(['module_id' => $modules[1]->id]);
        $moduleMod_3 = factory(ModuleMod::class)->create(['module_id' => $module_notAvailable->id]);
        $modPrice_0 = factory(ModPrice::class)->create([
            'module_mod_id' => $moduleMod_0->id,
            'package_id' => $packages[0]->id,
            'days' => 30,
            'price' => 10,
            'default' => 0,
        ]);
        $modPrice_1 = factory(ModPrice::class)->create([
            'module_mod_id' => $moduleMod_1->id,
            'package_id' => $packages[0]->id,
            'days' => 30,
            'price' => 20,
            'default' => 1,
        ]);
        $modPrice_2 = factory(ModPrice::class)->create([
            'module_mod_id' => $moduleMod_2->id,
            'package_id' => $packages[0]->id,
            'days' => 30,
            'price' => 40,
            'default' => 1,
        ]);
        $modPrice_3 = factory(ModPrice::class)->create([
            'module_mod_id' => $moduleMod_1->id,
            'package_id' => $packages[1]->id,
            'days' => 30,
            'price' => 50,
            'default' => 1,
        ]);

        $modPrice_4 = factory(ModPrice::class)->create([
            'module_mod_id' => $moduleMod_3->id,
            'package_id' => $packages[1]->id,
            'days' => 30,
            'price' => 70,
            'default' => 1,
        ]);

        $this->get('packages?selected_company_id=' . $company->id)
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => [
                    [
                        'id' => $packages[0]->id,
                        'slug' => $packages[0]->slug,
                        'name' => $packages[0]->name,
                        'default' => $packages[0]->default,
                        'portal_name' => $packages[0]->portal_name,
                        'price' => '60',
                        'days' => 30,
                    ],
                    [
                        'id' => $packages[1]->id,
                        'slug' => $packages[1]->slug,
                        'name' => $packages[1]->name,
                        'default' => $packages[1]->default,
                        'portal_name' => $packages[1]->portal_name,
                        'price' => '50',
                        'days' => 30,
                    ],
                ],
            ]);
    }

    /** @test */
    public function current_errorNoPermissionFortaxOffice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::TAX_OFFICE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages/current?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function current_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages/current?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function current_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages/current?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function current_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages/current?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function current_success()
    {
        config()->set('app_settings.package_portal_name', 'test');

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $package = factory(Package::class)->create(['portal_name' => 'test', 'slug' => 'test_name']);
        $modules = factory(Module::class)->create(['available' => 1]);
        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules->id]);
        $moduleMod = factory(ModuleMod::class)->create(['module_id' => $modules->id]);
        $modPrice = factory(ModPrice::class)->create([
            'module_mod_id' => $moduleMod->id,
            'package_id' => $package->id,
            'days' => 30,
            'price' => 10,
            'default' => 1,
        ]);
        factory(Module::class)->create(['available' => 0]);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            'test_name',
            Carbon::now()->addDay()->addYear()
        );

        auth()->loginUsingId($this->user->id);

        $companyModule = CompanyModule::where('module_id', $modules->id)->where('company_id', $company->id)->first();

        $this->get('packages/current?selected_company_id=' . $company->id)
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => [
                    'id' => $package->id,
                    'slug' => $package->slug,
                    'name' => $package->name,
                    'default' => $package->default,
                    'portal_name' => $package->portal_name,
                    'price' => 10,
                    'days' => 30,
                    'subscription' => [
                        'data' => null,
                    ],
                    'modules' => [
                        'data' => [[
                            'id' => $modules->id,
                            'name' => $modules->name,
                            'slug' => $modules->slug,
                            'description' => $modules->description,
                            'visible' => $modules->visible,
                            'available' => $modules->available,
                            'mods_count' => 1,
                            'company_module' => [
                                'data' => [
                                    'id' => $companyModule->id,
                                    'company_id' => $companyModule->company_id,
                                    'module_id' => $companyModule->module_id,
                                    'value' => $companyModule->value,
                                    'package_id' => $companyModule->package_id,
                                    'subscription_id' => $companyModule->subscription_id,
                                    'expiration_date' => $companyModule->expiration_date->toDateTimeString(),
                                    'created_at' => $companyModule->created_at->toDateTimeString(),
                                    'updated_at' => $companyModule->updated_at->toDateTimeString(),
                                ],
                            ],
                        ]],
                    ],
                ],
            ]);
    }

    /** @test */
    public function current_success_free()
    {
        config()->set('app_settings.package_portal_name', 'fv');

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);

        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages/current?selected_company_id=' . $company->id)->assertStatus(200);
        $json = $response->json()['data'];

        $this->assertSame(Package::START, $json['slug']);
        $this->assertSame(0, $json['price']);
        $this->assertSame(null, $json['days']);
        $this->assertSame(
            CompanyModule::where('company_id', $company->id)->whereNotNull('package_id')->count(),
            count($json['modules']['data'])
        );
    }

    /** @test */
    public function current_success_checkInfoSubscription()
    {
        config()->set('app_settings.package_portal_name', 'test');

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $subscription = factory(Subscription::class)->create();
        $package = factory(Package::class)->create(['portal_name' => 'test', 'slug' => 'test_name']);
        $modules = factory(Module::class)->create(['available' => 1]);
        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules->id]);
        $moduleMod = factory(ModuleMod::class)->create(['module_id' => $modules->id]);
        $modPrice = factory(ModPrice::class)->create([
            'module_mod_id' => $moduleMod->id,
            'package_id' => $package->id,
            'days' => 30,
            'price' => 10,
            'default' => 1,
        ]);
        factory(Module::class)->create(['available' => 0]);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            'test_name',
            Carbon::now()->addDay()->addYear()
        );

        auth()->loginUsingId($this->user->id);

        $companyModule = CompanyModule::where('company_id', $company->id)->whereNotNull('package_id')->update([
            'subscription_id' => $subscription->id,
        ]);

        $response = $this->get('packages/current?selected_company_id=' . $company->id)
            ->assertStatus(200);

        $this->assertSame(1, $response->json()['data']['subscription']['data']['active']);
        $this->assertSame($subscription->id, $response->json()['data']['subscription']['data']['id']);
    }

    /** @test */
    public function show_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $package = Package::findBySlug(Package::START);

        $response = $this->get('packages/' . $package->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function show_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $package = Package::findBySlug(Package::START);

        $response = $this->get('packages/' . $package->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function show_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $package = Package::findBySlug(Package::START);

        $response = $this->get('packages/' . $package->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function show_errorNotExist()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages/0?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function show_errorNotExistInSelectedPortal()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $package = Package::withoutGlobalScopes()->where('slug', Package::ICONTROL)->first();

        $response = $this->get('packages/' . $package->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function show_openCurrentFreeSuccess()
    {
        config()->set('app_settings.package_portal_name', 'test');

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $package = factory(Package::class)->create(['portal_name' => 'test', 'slug' => 'test_name']);
        $modules = factory(Module::class, 2)->create(['available' => 1]);
        $modules[0]->update(['slug' => 'invoices.proforma.enabled']);
        $modules[1]->update(['slug' => 'general.welcome_url']);

        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules[0]->id]);
        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules[1]->id]);
        $module_0_Mod = factory(ModuleMod::class, 2)->create(['module_id' => $modules[0]->id]);
        $module_1_Mod = factory(ModuleMod::class, 2)->create(['module_id' => $modules[1]->id]);

        $mod_0_Price = factory(ModPrice::class)->create([
            'module_mod_id' => $module_0_Mod[0]->id,
            'package_id' => $package->id,
            'days' => null,
            'price' => 0,
            'default' => 1,
        ]);
        $mod_1_Price = factory(ModPrice::class)->create([
            'module_mod_id' => $module_1_Mod[0]->id,
            'package_id' => $package->id,
            'days' => null,
            'price' => 0,
            'default' => 1,
        ]);

        factory(Module::class)->create(['available' => 0]);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            'test_name',
            Carbon::now()->addDays(365)
        );

        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages/' . $package->id . '?selected_company_id=' . $company->id);

        $json = $response->json()['data'];

        $this->assertSame(2, count($json));

        $this->assertSame($modules[0]->id, $json[0]['id']);
        $this->assertSame(1, count($json[0]['mods']['data']));
        $this->assertSame($module_0_Mod[0]->id, $json[0]['mods']['data'][0]['id']);
        $this->assertSame(ValidatorErrors::MODULE_MOD_CURRENTLY_USED, $json[0]['mods']['data'][0]['error']);
        $this->assertSame(1, count($json[0]['mods']['data'][0]['mod_prices']['data']));
        $this->assertSame($mod_0_Price->id, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['id']);
        $this->assertSame(0, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(0, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['checksum']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['checksum_change']);

        $this->assertSame($modules[1]->id, $json[1]['id']);
        $this->assertSame(1, count($json[1]['mods']['data']));
        $this->assertSame($module_1_Mod[0]->id, $json[1]['mods']['data'][0]['id']);
        $this->assertSame(false, $json[1]['mods']['data'][0]['error']);
        $this->assertSame(1, count($json[1]['mods']['data'][0]['mod_prices']['data']));
        $this->assertSame($mod_1_Price->id, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['id']);
        $this->assertSame(0, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(0, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(null, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['checksum']);
        $this->assertSame(null, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['checksum_change']);
    }

    /** @test */
    public function show_openCurrentPremium20DaysToEndSuccess()
    {
        config()->set('app_settings.package_portal_name', 'test');

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $package = factory(Package::class)->create(['portal_name' => 'test', 'slug' => 'test_name']);
        $modules = factory(Module::class, 2)->create(['available' => 1]);
        $modules[0]->update(['slug' => 'invoices.proforma.enabled']);
        $modules[1]->update(['slug' => 'general.welcome_url']);

        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules[0]->id]);
        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules[1]->id]);
        $module_0_Mod = [
            factory(ModuleMod::class)->create(['module_id' => $modules[0]->id, 'value' => 1]),
            factory(ModuleMod::class)->create(['module_id' => $modules[0]->id, 'value' => 2]),
        ];
        $module_1_Mod = [
            factory(ModuleMod::class)->create(['module_id' => $modules[1]->id, 'value' => 1]),
            factory(ModuleMod::class)->create(['module_id' => $modules[1]->id, 'value' => 2]),
        ];

        $mod_0_Price = [
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 100,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 10,
                'default' => 1,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[1]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 200,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[1]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 20,
            ]),
        ];

        $mod_1_Price = [
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_1_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 100,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_1_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 10,
                'default' => 1,
            ]),
        ];

        factory(Module::class)->create(['available' => 0]);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            'test_name',
            Carbon::now()->addDays(20)
        );

        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages/' . $package->id . '?selected_company_id=' . $company->id);

        $json = $response->json()['data'];

        $this->assertSame(2, count($json));

        //module 1
        $this->assertSame($modules[0]->id, $json[0]['id']);
        $this->assertSame(2, count($json[0]['mods']['data']));

        $this->assertSame($module_0_Mod[0]->id, $json[0]['mods']['data'][0]['id']);
        $this->assertSame(ValidatorErrors::MODULE_MOD_CURRENTLY_USED_CAN_EXTEND, $json[0]['mods']['data'][0]['error']);
        $this->assertSame(2, count($json[0]['mods']['data'][0]['mod_prices']['data']));

        $this->assertSame($mod_0_Price[0]->id, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['id']);
        $this->assertSame(100, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(5, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['checksum_change']);
        $this->checkChecksum($json[0]['mods']['data'][0]['mod_prices']['data'][0]);

        $this->assertSame($mod_0_Price[1]->id, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['id']);
        $this->assertSame(10, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price']);
        $this->assertSame(7, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price_change']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['checksum_change']);
        $this->checkChecksum($json[0]['mods']['data'][0]['mod_prices']['data'][1]);

        $this->assertSame($module_0_Mod[1]->id, $json[0]['mods']['data'][1]['id']);
        $this->assertSame(false, $json[0]['mods']['data'][1]['error']);
        $this->assertSame(2, count($json[0]['mods']['data'][1]['mod_prices']['data']));

        $this->assertSame($mod_0_Price[2]->id, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['id']);
        $this->assertSame(200, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price']);
        $this->assertSame(11, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['checksum_change']);
        $this->checkChecksum($json[0]['mods']['data'][1]['mod_prices']['data'][0]);

        $this->assertSame($mod_0_Price[3]->id, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['id']);
        $this->assertSame(20, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price']);
        $this->assertSame(13, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price_change']);
        $this->checkChecksumChange($json[0]['mods']['data'][1]['mod_prices']['data'][1]);
        $this->checkChecksum($json[0]['mods']['data'][1]['mod_prices']['data'][1]);

        //module 2
        $this->assertSame($modules[1]->id, $json[1]['id']);
        $this->assertSame(1, count($json[1]['mods']['data']));
        $this->assertSame($module_1_Mod[0]->id, $json[1]['mods']['data'][0]['id']);
        $this->assertSame(false, $json[1]['mods']['data'][0]['error']);
        $this->assertSame(2, count($json[1]['mods']['data'][0]['mod_prices']['data']));

        $this->assertSame($mod_1_Price[0]->id, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['id']);
        $this->assertSame(100, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(5, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->checkChecksum($json[1]['mods']['data'][0]['mod_prices']['data'][0]);

        $this->assertSame($mod_1_Price[1]->id, $json[1]['mods']['data'][0]['mod_prices']['data'][1]['id']);
        $this->assertSame(10, $json[1]['mods']['data'][0]['mod_prices']['data'][1]['price']);
        $this->assertSame(7, $json[1]['mods']['data'][0]['mod_prices']['data'][1]['price_change']);
        $this->checkChecksum($json[1]['mods']['data'][0]['mod_prices']['data'][1]);
    }

    /** @test */
    public function show_openCurrentPremium300DaysToEndAndCheckNotUsedModuleFromPackageSuccess()
    {
        config()->set('app_settings.package_portal_name', 'test');

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $package = factory(Package::class)->create(['portal_name' => 'test', 'slug' => 'test_name']);
        $modules = factory(Module::class, 3)->create(['available' => 1]);
        $modules[0]->update(['slug' => 'invoices.proforma.enabled']);
        $modules[1]->update(['slug' => 'general.welcome_url']);
        $modules[2]->update(['slug' => 'invoices.registry.export.name']);

        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules[0]->id]);
        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules[1]->id]);
        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules[2]->id]);
        $module_0_Mod = [
            factory(ModuleMod::class)->create(['module_id' => $modules[0]->id, 'value' => 1]),
            factory(ModuleMod::class)->create(['module_id' => $modules[0]->id, 'value' => 2]),
        ];
        $module_1_Mod = [
            factory(ModuleMod::class)->create(['module_id' => $modules[1]->id, 'value' => 1]),
            factory(ModuleMod::class)->create(['module_id' => $modules[1]->id, 'value' => 2]),
        ];
        $module_2_Mod = [
            factory(ModuleMod::class)->create(['module_id' => $modules[2]->id, 'value' => '']),
            factory(ModuleMod::class)->create(['module_id' => $modules[2]->id, 'value' => 'optima']),
        ];

        $mod_0_Price = [
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 100,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 10,
                'default' => 1,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[1]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 200,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[1]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 20,
            ]),
        ];

        $mod_1_Price = [
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_1_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 100,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_1_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 10,
                'default' => 1,
            ]),
        ];

        $mod_2_Price = [
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_2_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 0,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_2_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 0,
                'default' => 1,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_2_Mod[1]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 1000,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_2_Mod[1]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 100,
            ]),
        ];

        factory(Module::class)->create(['available' => 0]);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            'test_name',
            Carbon::now()->addDays(300)
        );

        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages/' . $package->id . '?selected_company_id=' . $company->id);

        $json = $response->json()['data'];

        $this->assertSame(3, count($json));

        //module 1
        $this->assertSame($modules[0]->id, $json[0]['id']);
        $this->assertSame(2, count($json[0]['mods']['data']));

        $this->assertSame($module_0_Mod[0]->id, $json[0]['mods']['data'][0]['id']);
        $this->assertSame(ValidatorErrors::MODULE_MOD_CURRENTLY_USED, $json[0]['mods']['data'][0]['error']);
        $this->assertSame(2, count($json[0]['mods']['data'][0]['mod_prices']['data']));

        $this->assertSame($mod_0_Price[0]->id, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['id']);
        $this->assertSame(100, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(82, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['checksum_change']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['checksum']);

        $this->assertSame($mod_0_Price[1]->id, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['id']);
        $this->assertSame(10, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price']);
        $this->assertSame(10, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price_change']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['checksum_change']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['checksum']);

        $this->assertSame($module_0_Mod[1]->id, $json[0]['mods']['data'][1]['id']);
        $this->assertSame(false, $json[0]['mods']['data'][1]['error']);
        $this->assertSame(2, count($json[0]['mods']['data'][1]['mod_prices']['data']));

        $this->assertSame($mod_0_Price[2]->id, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['id']);
        $this->assertSame(200, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price']);
        $this->assertSame(164, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price_change']);
        $this->checkChecksumChange($json[0]['mods']['data'][1]['mod_prices']['data'][0]);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['checksum']);

        $this->assertSame($mod_0_Price[3]->id, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['id']);
        $this->assertSame(20, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price']);
        $this->assertSame(20, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price_change']);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['checksum_change']);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['checksum']);

        //module 2
        $this->assertSame($modules[1]->id, $json[1]['id']);
        $this->assertSame(1, count($json[1]['mods']['data']));
        $this->assertSame($module_1_Mod[0]->id, $json[1]['mods']['data'][0]['id']);
        $this->assertSame(false, $json[1]['mods']['data'][0]['error']);
        $this->assertSame(2, count($json[1]['mods']['data'][0]['mod_prices']['data']));

        $this->assertSame($mod_1_Price[0]->id, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['id']);
        $this->assertSame(100, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(82, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(null, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['checksum']);

        $this->assertSame($mod_1_Price[1]->id, $json[1]['mods']['data'][0]['mod_prices']['data'][1]['id']);
        $this->assertSame(10, $json[1]['mods']['data'][0]['mod_prices']['data'][1]['price']);
        $this->assertSame(10, $json[1]['mods']['data'][0]['mod_prices']['data'][1]['price_change']);
        $this->assertSame(null, $json[1]['mods']['data'][0]['mod_prices']['data'][1]['checksum']);

        //module 3
        $this->assertSame($modules[2]->id, $json[2]['id']);
        $this->assertSame(2, count($json[2]['mods']['data']));

        $this->assertSame($module_2_Mod[0]->id, $json[2]['mods']['data'][0]['id']);
        $this->assertSame(ValidatorErrors::MODULE_MOD_CURRENTLY_USED, $json[2]['mods']['data'][0]['error']);
        $this->assertSame(2, count($json[2]['mods']['data'][0]['mod_prices']['data']));

        $this->assertSame($mod_2_Price[0]->id, $json[2]['mods']['data'][0]['mod_prices']['data'][0]['id']);
        $this->assertSame(0, $json[2]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(0, $json[2]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(null, $json[2]['mods']['data'][0]['mod_prices']['data'][0]['checksum_change']);
        $this->assertSame(null, $json[2]['mods']['data'][0]['mod_prices']['data'][0]['checksum']);

        $this->assertSame($mod_2_Price[1]->id, $json[2]['mods']['data'][0]['mod_prices']['data'][1]['id']);
        $this->assertSame(0, $json[2]['mods']['data'][0]['mod_prices']['data'][1]['price']);
        $this->assertSame(0, $json[2]['mods']['data'][0]['mod_prices']['data'][1]['price_change']);
        $this->assertSame(null, $json[2]['mods']['data'][0]['mod_prices']['data'][1]['checksum_change']);
        $this->assertSame(null, $json[2]['mods']['data'][0]['mod_prices']['data'][1]['checksum']);

        $this->assertSame($module_2_Mod[1]->id, $json[2]['mods']['data'][1]['id']);
        $this->assertSame(false, $json[2]['mods']['data'][1]['error']);
        $this->assertSame(2, count($json[2]['mods']['data'][1]['mod_prices']['data']));

        $this->assertSame($mod_2_Price[2]->id, $json[2]['mods']['data'][1]['mod_prices']['data'][0]['id']);
        $this->assertSame(1000, $json[2]['mods']['data'][1]['mod_prices']['data'][0]['price']);
        $this->assertSame(822, $json[2]['mods']['data'][1]['mod_prices']['data'][0]['price_change']);
        $this->checkChecksumChange($json[2]['mods']['data'][1]['mod_prices']['data'][0]);
        $this->assertSame(null, $json[2]['mods']['data'][1]['mod_prices']['data'][0]['checksum']);

        $this->assertSame($mod_2_Price[3]->id, $json[2]['mods']['data'][1]['mod_prices']['data'][1]['id']);
        $this->assertSame(100, $json[2]['mods']['data'][1]['mod_prices']['data'][1]['price']);
        $this->assertSame(100, $json[2]['mods']['data'][1]['mod_prices']['data'][1]['price_change']);
        $this->assertSame(null, $json[2]['mods']['data'][1]['mod_prices']['data'][1]['checksum_change']);
        $this->assertSame(null, $json[2]['mods']['data'][1]['mod_prices']['data'][1]['checksum']);
    }

    /** @test */
    public function show_openOtherSuccess()
    {
        config()->set('app_settings.package_portal_name', 'test');

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $package_current = factory(Package::class)->create(['portal_name' => 'test', 'slug' => 'current_test_name']);
        $package = factory(Package::class)->create(['portal_name' => 'test', 'slug' => 'test_name']);
        $modules = factory(Module::class, 2)->create(['available' => 1]);
        $modules[0]->update(['slug' => 'invoices.proforma.enabled']);
        $modules[1]->update(['slug' => 'general.welcome_url']);

        factory(PackageModule::class)->create(['package_id' => $package_current->id, 'module_id' => $modules[0]->id]);
        factory(PackageModule::class)->create(['package_id' => $package_current->id, 'module_id' => $modules[1]->id]);
        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules[0]->id]);
        factory(PackageModule::class)->create(['package_id' => $package->id, 'module_id' => $modules[1]->id]);
        $module_0_Mod = [
            factory(ModuleMod::class)->create(['module_id' => $modules[0]->id, 'value' => 1]),
            factory(ModuleMod::class)->create(['module_id' => $modules[0]->id, 'value' => 2]),
        ];
        $module_1_Mod = [
            factory(ModuleMod::class)->create(['module_id' => $modules[1]->id, 'value' => 1]),
            factory(ModuleMod::class)->create(['module_id' => $modules[1]->id, 'value' => 2]),
        ];

        $mod_0_Price = [
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 100,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 10,
                'default' => 1,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[1]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 200,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[1]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 20,
            ]),

            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[0]->id,
                'package_id' => $package_current->id,
                'days' => 365,
                'price' => 100,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_0_Mod[0]->id,
                'package_id' => $package_current->id,
                'days' => 30,
                'price' => 10,
                'default' => 1,
            ]),
        ];

        $mod_1_Price = [
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_1_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 365,
                'price' => 100,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_1_Mod[0]->id,
                'package_id' => $package->id,
                'days' => 30,
                'price' => 10,
                'default' => 1,
            ]),

            factory(ModPrice::class)->create([
                'module_mod_id' => $module_1_Mod[0]->id,
                'package_id' => $package_current->id,
                'days' => 365,
                'price' => 100,
            ]),
            factory(ModPrice::class)->create([
                'module_mod_id' => $module_1_Mod[0]->id,
                'package_id' => $package_current->id,
                'days' => 30,
                'price' => 10,
                'default' => 1,
            ]),
        ];

        factory(Module::class)->create(['available' => 0]);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            'current_test_name',
            Carbon::now()->addDays(20)
        );

        auth()->loginUsingId($this->user->id);

        $response = $this->get('packages/' . $package->id . '?selected_company_id=' . $company->id);

        $json = $response->json()['data'];

        $this->assertSame(2, count($json));

        //module 1
        $this->assertSame($modules[0]->id, $json[0]['id']);
        $this->assertSame(2, count($json[0]['mods']['data']));

        $this->assertSame($module_0_Mod[0]->id, $json[0]['mods']['data'][0]['id']);
        $this->assertSame(ValidatorErrors::MODULE_MOD_CURRENTLY_USED_CAN_EXTEND, $json[0]['mods']['data'][0]['error']);
        $this->assertSame(2, count($json[0]['mods']['data'][0]['mod_prices']['data']));

        $this->assertSame($mod_0_Price[0]->id, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['id']);
        $this->assertSame(100, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(5, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(null, $json[0]['mods']['data'][0]['mod_prices']['data'][0]['checksum_change']);
        $this->checkChecksum($json[0]['mods']['data'][0]['mod_prices']['data'][0]);

        $this->assertSame($mod_0_Price[1]->id, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['id']);
        $this->assertSame(10, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price']);
        $this->assertSame(7, $json[0]['mods']['data'][0]['mod_prices']['data'][1]['price_change']);
        $this->checkChecksumChange($json[0]['mods']['data'][0]['mod_prices']['data'][1]);
        $this->checkChecksum($json[0]['mods']['data'][0]['mod_prices']['data'][1]);

        $this->assertSame($module_0_Mod[1]->id, $json[0]['mods']['data'][1]['id']);
        $this->assertSame(false, $json[0]['mods']['data'][1]['error']);
        $this->assertSame(2, count($json[0]['mods']['data'][1]['mod_prices']['data']));

        $this->assertSame($mod_0_Price[2]->id, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['id']);
        $this->assertSame(200, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price']);
        $this->assertSame(11, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['price_change']);
        $this->assertSame(null, $json[0]['mods']['data'][1]['mod_prices']['data'][0]['checksum_change']);
        $this->checkChecksum($json[0]['mods']['data'][1]['mod_prices']['data'][0]);

        $this->assertSame($mod_0_Price[3]->id, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['id']);
        $this->assertSame(20, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price']);
        $this->assertSame(13, $json[0]['mods']['data'][1]['mod_prices']['data'][1]['price_change']);
        $this->checkChecksumChange($json[0]['mods']['data'][1]['mod_prices']['data'][1]);
        $this->checkChecksum($json[0]['mods']['data'][1]['mod_prices']['data'][1]);

        //module 2
        $this->assertSame($modules[1]->id, $json[1]['id']);
        $this->assertSame(1, count($json[1]['mods']['data']));
        $this->assertSame($module_1_Mod[0]->id, $json[1]['mods']['data'][0]['id']);
        $this->assertSame(false, $json[1]['mods']['data'][0]['error']);
        $this->assertSame(2, count($json[1]['mods']['data'][0]['mod_prices']['data']));

        $this->assertSame($mod_1_Price[0]->id, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['id']);
        $this->assertSame(100, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['price']);
        $this->assertSame(5, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['price_change']);
        $this->checkChecksum($json[1]['mods']['data'][0]['mod_prices']['data'][0]);
        $this->assertSame(null, $json[1]['mods']['data'][0]['mod_prices']['data'][0]['checksum_change']);

        $this->assertSame($mod_1_Price[1]->id, $json[1]['mods']['data'][0]['mod_prices']['data'][1]['id']);
        $this->assertSame(10, $json[1]['mods']['data'][0]['mod_prices']['data'][1]['price']);
        $this->assertSame(7, $json[1]['mods']['data'][0]['mod_prices']['data'][1]['price_change']);
        $this->checkChecksum($json[1]['mods']['data'][0]['mod_prices']['data'][1]);
        $this->checkChecksumChange($json[1]['mods']['data'][0]['mod_prices']['data'][1]);
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
