<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\ModPrice;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;
use App\Models\Db\Package;
use App\Models\Db\Payment;
use App\Models\Db\Subscription;
use App\Models\Db\Transaction;
use App\Models\Other\PaymentStatus;
use App\Models\Other\RoleType;
use App\Modules\Company\Services\Payments\Crypter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;

class ModuleControllerOtherTest extends TestCase
{
    use DatabaseTransactions, ExtendModule;

    protected function setUp():void
    {
        parent::setUp();

        $this->createTestExtendModule();
    }

    /** @test */
    public function store_errorNoPermissionForTaxOffice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::TAX_OFFICE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('modules?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('modules?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('modules?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('modules?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_errorEmptyData()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('modules?selected_company_id=' . $company->id, []);

        $this->verifyResponseValidation($response, ['days', 'is_test', 'currency', 'mod_price_id', 'checksum']);
    }

    /** @test */
    public function store_errorWrongData()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $data = [
            'days' => 1,
            'is_test' => 2,
            'currency' => 'USD',
            'mod_price_id' => 0,
        ];

        $response = $this->post('modules?selected_company_id=' . $company->id, $data);

        $this->verifyResponseValidation($response, ['days', 'is_test', 'currency', 'mod_price_id']);
    }

    /** @test */
    public function store_errorWrongDays()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $mod_price = factory(ModPrice::class)->create(['days' => 30, 'currency' => 'PLN']);

        $data = [
            'days' => 1,
            'is_test' => 1,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
        ];

        $response = $this->post('modules?selected_company_id=' . $company->id, $data);

        $this->verifyResponseValidation($response, ['days', 'mod_price_id'], ['is_test', 'currency']);
    }

    /** @test */
    public function store_errorWrongCurrency()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $mod_price = factory(ModPrice::class)->create(['days' => 30, 'currency' => 'PLN']);

        $data = [
            'days' => 30,
            'is_test' => 1,
            'currency' => 'USD',
            'mod_price_id' => $mod_price->id,
        ];

        $response = $this->post('modules?selected_company_id=' . $company->id, $data);

        $this->verifyResponseValidation($response, ['currency', 'mod_price_id'], ['is_test', 'days']);
    }

    /** @test */
    public function store_errorWithoutCurrencyAndDays()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $mod_price = factory(ModPrice::class)->create(['days' => 30, 'currency' => 'PLN']);

        $data = [
            'is_test' => 1,
            'mod_price_id' => $mod_price->id,
        ];

        $response = $this->post('modules?selected_company_id=' . $company->id, $data);

        $this->verifyResponseValidation($response, ['currency', 'days', 'mod_price_id'], ['is_test']);
    }

    /** @test */
    public function store_errorWrongOnlyIsTest()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $mod_price = factory(ModPrice::class)->create(['days' => 30, 'currency' => 'PLN']);

        $data = [
            'days' => 30,
            'is_test' => 2,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
        ];

        $response = $this->post('modules?selected_company_id=' . $company->id, $data);

        $this->verifyResponseValidation($response, ['is_test'], ['currency', 'days', 'mod_price_id']);
    }

    /** @test */
    public function store_errorChecksum()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $data = [
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => 'dsada',
        ];

        $response = $this->post('modules?selected_company_id=' . $company->id, $data);

        $this->verifyResponseError($response, 409, ErrorCode::PACKAGE_DATA_CONSISTENCY_ERROR);
    }

    /** @test */
    public function store_errorChecksumExpired()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $data = [
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt($mod_price->id, $mod_price->price),
        ];

        Carbon::setTestNow($now->addMinutes(11));

        $response = $this->post('modules?selected_company_id=' . $company->id, $data);

        $this->verifyResponseError($response, 409, ErrorCode::PACKAGE_DATA_CONSISTENCY_ERROR);
    }

    /** @test */
    public function store_errorChecksumWrongId()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $data = [
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt(0, $mod_price->price),
        ];

        $response = $this->post('modules?selected_company_id=' . $company->id, $data);

        $this->verifyResponseError($response, 409, ErrorCode::PACKAGE_DATA_CONSISTENCY_ERROR);
    }

    /** @test */
    public function store_errorChecksumWrongPrice()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $data = [
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt($mod_price->id, 999999999),
        ];

        $response = $this->post('modules?selected_company_id=' . $company->id, $data);

        $this->verifyResponseError($response, 409, ErrorCode::PACKAGE_DATA_CONSISTENCY_ERROR);
    }

    /** @test */
    public function store_errorIsNotTestButSendTest()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $data = [
            'days' => 30,
            'is_test' => 1,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt($mod_price->id, $mod_price->price),
        ];

        $response = $this->post('modules?selected_company_id=' . $company->id, $data);

        $this->verifyResponseError($response, 409, ErrorCode::PACKAGE_DATA_CONSISTENCY_ERROR);
    }

    /** @test */
    public function store_successTest()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'test' => 1, 'value' => 'test1']);
        $mod_price = factory(ModPrice::class)->create([
            'module_mod_id' => $mod->id,
            'package_id' => null,
            'days' => 30,
            'price' => 0,
            'currency' => 'PLN',
        ]);

        $data = [
            'days' => 30,
            'is_test' => 1,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt($mod_price->id, $mod_price->price),
        ];

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $json = $this->post('modules?selected_company_id=' . $company->id, $data)
            ->assertStatus(200)->json()['data'];

        $this->assertSame($count_payments, Payment::count());
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - 1);

        $this->assertSame(Transaction::orderByDesc('id')->first()->id, $json['id']);
        $this->assertSame(0, count($json['payments']['data']));

        $company_module = CompanyModule::orderByDesc('id')->first();
        $this->assertSame($company->id, $company_module->company_id);
        $this->assertSame($module->id, $company_module->module_id);
        $this->assertSame('test1', $company_module->value);
        $this->assertSame(null, $company_module->package_id);
        $this->assertSame(Carbon::now()->addDays(30)->toDateTimeString(), $company_module->expiration_date->toDateTimeString());
        $this->assertSame(null, $company_module->subscription_id);

        $history = CompanyModuleHistory::where('transaction_id', $json['id'])->first();
        $this->assertSame($company->id, $history->company_id);
        $this->assertSame($module->id, $history->module_id);
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('', $history->old_value);
        $this->assertSame('test1', $history->new_value);
        $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
        $this->assertSame(Carbon::now()->addDays(30)->toDateTimeString(), $history->expiration_date->toDateTimeString());
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame(0, $history->price);
        $this->assertSame('PLN', $history->currency);
        $this->assertSame($json['id'], $history->transaction_id);
    }

    /** @test */
    public function store_successFree()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = factory(ModuleMod::class)->create(['module_id' => $module->id, 'test' => 0, 'value' => 'test1']);
        $mod_price = factory(ModPrice::class)->create([
            'module_mod_id' => $mod->id,
            'package_id' => null,
            'days' => 30,
            'price' => 0,
            'currency' => 'PLN',
        ]);

        $data = [
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt($mod_price->id, $mod_price->price),
        ];

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $json = $this->post('modules?selected_company_id=' . $company->id, $data)
            ->assertStatus(200)->json()['data'];

        $this->assertSame($count_payments, Payment::count());
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - 1);

        $this->assertSame(Transaction::orderByDesc('id')->first()->id, $json['id']);
        $this->assertSame(0, count($json['payments']['data']));

        $company_module = CompanyModule::orderByDesc('id')->first();
        $this->assertSame($company->id, $company_module->company_id);
        $this->assertSame($module->id, $company_module->module_id);
        $this->assertSame('test1', $company_module->value);
        $this->assertSame(null, $company_module->package_id);
        $this->assertSame(Carbon::now()->addDays(30)->toDateTimeString(), $company_module->expiration_date->toDateTimeString());
        $this->assertSame(null, $company_module->subscription_id);

        $history = CompanyModuleHistory::where('transaction_id', $json['id'])->first();
        $this->assertSame($company->id, $history->company_id);
        $this->assertSame($module->id, $history->module_id);
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('', $history->old_value);
        $this->assertSame('test1', $history->new_value);
        $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
        $this->assertSame(Carbon::now()->addDays(30)->toDateTimeString(), $history->expiration_date->toDateTimeString());
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame(0, $history->price);
        $this->assertSame('PLN', $history->currency);
        $this->assertSame($json['id'], $history->transaction_id);
    }

    /** @test */
    public function store_successPremium_firstBuy()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $expiration_date_package = Carbon::now()->addDays(30);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, $expiration_date_package);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $data = [
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt($mod_price->id, $mod_price->price),
        ];

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $json = $this->post('modules?selected_company_id=' . $company->id, $data)
            ->assertStatus(200)->json()['data'];

        $this->assertSame($count_payments, Payment::count() - 1);
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - 1);

        $payment = Payment::orderByDesc('id')->first();

        $this->assertSame(Transaction::orderByDesc('id')->first()->id, $json['id']);
        $this->assertSame(1, count($json['payments']['data']));
        $this->assertSame($mod_price->price, $json['payments']['data'][0]['price_total']);
        $this->assertSame($payment->id, $json['payments']['data'][0]['id']);

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('', $company_module->value);

        $history = CompanyModuleHistory::where('transaction_id', $json['id'])->first();
        $this->assertSame($company->id, $history->company_id);
        $this->assertSame($module->id, $history->module_id);
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('', $history->old_value);
        $this->assertSame('test1', $history->new_value);
        $this->assertSame(null, $history->start_date);
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame($mod_price->price, $history->price);
        $this->assertSame($mod_price->currency, $history->currency);
        $this->assertSame($mod_price->vat, $history->vat);
        $this->assertSame($json['id'], $history->transaction_id);

        //payment
        $this->assertSame($mod_price->price, $payment->price_total);
        $this->assertSame((int) ceil($mod_price->price * 23 / 123), $payment->vat);
        $this->assertSame($mod_price->currency, $payment->currency);
        $this->assertSame(null, $payment->external_order_id);
        $this->assertSame(PaymentStatus::STATUS_BEFORE_START, $payment->status);
        $this->assertSame(null, $payment->type);
        $this->assertSame(null, $payment->subscription_id);
        $this->assertSame(30, $payment->days);
        $this->assertSame($expiration_date_package->toDateTimeString(), $payment->expiration_date->toDateTimeString());
        $this->assertSame($json['id'], $payment->transaction_id);
    }

    /** @test */
    public function store_successPremium_renew20Days()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $expiration_date_package = Carbon::now()->addDays(20);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, $expiration_date_package);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $expiration_date = Carbon::now()->addDays(10);
        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => 'test',
            'expiration_date' => $expiration_date,
        ]);

        $price = (int) round($mod_price->price / 30 * 20);
        $vat = (int) ceil($price * 23 / 123);

        $data = [
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt($mod_price->id, $price),
        ];

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $json = $this->post('modules?selected_company_id=' . $company->id, $data)
            ->assertStatus(200)->json()['data'];

        $this->assertSame($count_payments, Payment::count() - 1);
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - 1);

        $payment = Payment::orderByDesc('id')->first();

        $this->assertSame(Transaction::orderByDesc('id')->first()->id, $json['id']);
        $this->assertSame(1, count($json['payments']['data']));
        $this->assertSame($price, $json['payments']['data'][0]['price_total']);
        $this->assertSame($payment->id, $json['payments']['data'][0]['id']);

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('test', $company_module->value);

        $history = CompanyModuleHistory::where('transaction_id', $json['id'])->first();
        $this->assertSame($company->id, $history->company_id);
        $this->assertSame($module->id, $history->module_id);
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('test', $history->old_value);
        $this->assertSame('test1', $history->new_value);
        $this->assertSame($expiration_date->toDateTimeString(), $history->start_date->toDateTimeString());
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame($price, $history->price);
        $this->assertSame($mod_price->currency, $history->currency);
        $this->assertSame($json['id'], $history->transaction_id);

        //payment
        $this->assertSame($price, $payment->price_total);
        $this->assertSame($vat, $payment->vat);
        $this->assertSame($mod_price->currency, $payment->currency);
        $this->assertSame(null, $payment->external_order_id);
        $this->assertSame(PaymentStatus::STATUS_BEFORE_START, $payment->status);
        $this->assertSame(null, $payment->type);
        $this->assertSame(null, $payment->subscription_id);
        $this->assertSame(20, $payment->days);
        $this->assertSame($expiration_date_package->toDateTimeString(), $payment->expiration_date->toDateTimeString());
        $this->assertSame($json['id'], $payment->transaction_id);
    }

    /** @test */
    public function store_successPremium_renewWithExpierdTime()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $expiration_date_package = Carbon::now()->addDays(30);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, $expiration_date_package);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => 'test',
            'expiration_date' => Carbon::now()->subDay(),
        ]);

        $data = [
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt($mod_price->id, $mod_price->price),
        ];

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $json = $this->post('modules?selected_company_id=' . $company->id, $data)
            ->assertStatus(200)->json()['data'];

        $this->assertSame($count_payments, Payment::count() - 1);
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - 1);

        $payment = Payment::orderByDesc('id')->first();

        $this->assertSame(Transaction::orderByDesc('id')->first()->id, $json['id']);
        $this->assertSame(1, count($json['payments']['data']));
        $this->assertSame($mod_price->price, $json['payments']['data'][0]['price_total']);
        $this->assertSame($payment->id, $json['payments']['data'][0]['id']);

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('test', $company_module->value);

        $history = CompanyModuleHistory::where('transaction_id', $json['id'])->first();
        $this->assertSame($company->id, $history->company_id);
        $this->assertSame($module->id, $history->module_id);
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('test', $history->old_value);
        $this->assertSame('test1', $history->new_value);
        $this->assertSame(null, $history->start_date);
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame($mod_price->price, $history->price);
        $this->assertSame($mod_price->currency, $history->currency);
        $this->assertSame($mod_price->vat, $history->vat);
        $this->assertSame($json['id'], $history->transaction_id);

        //payment
        $this->assertSame($mod_price->price, $payment->price_total);
        $this->assertSame((int) ceil($mod_price->price * 23 / 123), $payment->vat);
        $this->assertSame($mod_price->currency, $payment->currency);
        $this->assertSame(null, $payment->external_order_id);
        $this->assertSame(PaymentStatus::STATUS_BEFORE_START, $payment->status);
        $this->assertSame(null, $payment->type);
        $this->assertSame(null, $payment->subscription_id);
        $this->assertSame(30, $payment->days);
        $this->assertSame($expiration_date_package->toDateTimeString(), $payment->expiration_date->toDateTimeString());
        $this->assertSame($json['id'], $payment->transaction_id);
    }

    /** @test */
    public function store_successUpgradeExternalModule()
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
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $expiration_date = Carbon::now()->addDays(20);
        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => 'test',
            'expiration_date' => $expiration_date,
        ]);

        $price = (int) round($mod_price->price / 30 * 20);
        $vat = (int) ceil($price * 23 / 123);
        $data = [
            'days' => 0,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt($mod_price->id, $price),
        ];

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $json = $this->post('modules?selected_company_id=' . $company->id, $data)
            ->assertStatus(200)->json()['data'];

        $this->assertSame($count_payments, Payment::count() - 1);
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - 1);

        $payment = Payment::orderByDesc('id')->first();

        $this->assertSame(Transaction::orderByDesc('id')->first()->id, $json['id']);
        $this->assertSame(1, count($json['payments']['data']));
        $this->assertSame($price, $json['payments']['data'][0]['price_total']);
        $this->assertSame($payment->id, $json['payments']['data'][0]['id']);

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('test', $company_module->value);

        $history = CompanyModuleHistory::where('transaction_id', $json['id'])->first();
        $this->assertSame($company->id, $history->company_id);
        $this->assertSame($module->id, $history->module_id);
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('test', $history->old_value);
        $this->assertSame('test1', $history->new_value);
        $this->assertSame(null, $history->start_date);
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame($price, $history->price);
        $this->assertSame($mod_price->currency, $history->currency);
        $this->assertSame($json['id'], $history->transaction_id);

        //payment
        $this->assertSame($price, $payment->price_total);
        $this->assertSame($vat, $payment->vat);
        $this->assertSame($mod_price->currency, $payment->currency);
        $this->assertSame(null, $payment->external_order_id);
        $this->assertSame(PaymentStatus::STATUS_BEFORE_START, $payment->status);
        $this->assertSame(null, $payment->type);
        $this->assertSame(null, $payment->subscription_id);
        $this->assertSame(null, $payment->days);
        $this->assertSame($expiration_date->toDateTimeString(), $payment->expiration_date->toDateTimeString());
        $this->assertSame($json['id'], $payment->transaction_id);
    }

    /** @test */
    public function store_successUpgradePackageModule()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $premium_package = Package::where('slug', Package::PREMIUM)->first();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            Package::PREMIUM,
            Carbon::now()->addDays(20)
        );
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('invoices.registry.export.name');
        $mod = $module->mods()->where('value', 'optima')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $expiration_date = Carbon::now()->addDays(20);
        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => '',
            'expiration_date' => $expiration_date,
        ]);

        $price = (int) round($mod_price->price / 30 * 20);
        $vat = (int) ceil($price * 23 / 123);
        $data = [
            'days' => 0,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price_id' => $mod_price->id,
            'checksum' => Crypter::encrypt($mod_price->id, $price),
        ];

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $json = $this->post('modules?selected_company_id=' . $company->id, $data)
            ->assertStatus(200)->json()['data'];

        $this->assertSame($count_payments, Payment::count() - 1);
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - 1);

        $payment = Payment::orderByDesc('id')->first();

        $this->assertSame(Transaction::orderByDesc('id')->first()->id, $json['id']);
        $this->assertSame(1, count($json['payments']['data']));
        $this->assertSame($price, $json['payments']['data'][0]['price_total']);
        $this->assertSame($payment->id, $json['payments']['data'][0]['id']);

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('', $company_module->value);

        $history = CompanyModuleHistory::where('transaction_id', $json['id'])->first();
        $this->assertSame($company->id, $history->company_id);
        $this->assertSame($module->id, $history->module_id);
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('', $history->old_value);
        $this->assertSame('optima', $history->new_value);
        $this->assertSame(null, $history->start_date);
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history->status);
        $this->assertSame($premium_package->id, $history->package_id);
        $this->assertSame($price, $history->price);
        $this->assertSame($mod_price->currency, $history->currency);
        $this->assertSame($json['id'], $history->transaction_id);

        //payment
        $this->assertSame($price, $payment->price_total);
        $this->assertSame($vat, $payment->vat);
        $this->assertSame($mod_price->currency, $payment->currency);
        $this->assertSame(null, $payment->external_order_id);
        $this->assertSame(PaymentStatus::STATUS_BEFORE_START, $payment->status);
        $this->assertSame(null, $payment->type);
        $this->assertSame(null, $payment->subscription_id);
        $this->assertSame(null, $payment->days);
        $this->assertSame($expiration_date->toDateTimeString(), $payment->expiration_date->toDateTimeString());
        $this->assertSame($json['id'], $payment->transaction_id);
    }

    /** @test */
    public function destroy_errorNoPermissionForTaxOffice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::TAX_OFFICE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->delete('modules/1?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function destroy_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->delete('modules/1?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function destroy_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->delete('modules/1?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function destroy_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->delete('modules/1?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function destroy_errorNotFound_WrongId()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $response = $this->delete('modules/0?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function destroy_errorNotFound_ModuleFromPackage()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('invoices.active');

        $response = $this->delete('modules/' . $module->id . '?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function destroy_errorNotFound_hasActiveSubscription()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $subscription = factory(Subscription::class)->create();
        $module = Module::findBySlug('test.extend.module');

        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => 'test',
            'subscription_id' => $subscription->id,
        ]);

        $response = $this->delete('modules/' . $module->id . '?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function destroy_success_withoutSubscription()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', '')->first();

        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => 'test',
        ]);

        $this->delete('modules/' . $module->id . '?selected_company_id=' . $company->id, [])
            ->assertStatus(204);

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
        $this->assertSame('test', $history->old_value);
        $this->assertSame('', $history->new_value);
        $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame(0, $history->price);
        $this->assertSame('PLN', $history->currency);
    }

    /** @test */
    public function destroy_success_notActiveSubscription()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $subscription = factory(Subscription::class)->create(['active' => false]);
        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', '')->first();

        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => 'test',
            'subscription_id' => $subscription->id,
        ]);

        $this->delete('modules/' . $module->id . '?selected_company_id=' . $company->id, [])
            ->assertStatus(204);

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
        $this->assertSame('test', $history->old_value);
        $this->assertSame('', $history->new_value);
        $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame(0, $history->price);
        $this->assertSame('PLN', $history->currency);
    }
}
