<?php

namespace  Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\ModPrice;
use App\Models\Db\Package;
use App\Models\Db\Payment;
use App\Models\Db\Transaction;
use App\Models\Other\PaymentStatus;
use App\Models\Other\RoleType;
use App\Modules\Company\Services\Payments\Crypter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;

class PackageControllerOtherTest extends TestCase
{
    /**
     * COMMON ELEMENTS WAS TESTED IN ModuleControllerOtherTest.
     */
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

        $response = $this->post('packages?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('packages?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('packages?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('packages?selected_company_id=' . $company->id, []);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_errorEmptyData()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('packages?selected_company_id=' . $company->id, []);

        $this->verifyResponseValidation(
            $response,
            ['package_id', 'days', 'is_test', 'currency', 'mod_price']
        );
    }

    /** @test */
    public function store_errorWrongData()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $data = [
            'package_id' => 0,
            'days' => 1,
            'is_test' => 2,
            'currency' => 'USD',
            'mod_price' => 0,
        ];

        $response = $this->post('packages?selected_company_id=' . $company->id, $data);

        $this->verifyResponseValidation($response, ['package_id', 'days', 'is_test', 'currency', 'mod_price']);
    }

    /** @test */
    public function store_errorContentWithoutCurrencyAndDaysArray()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $mod_price = factory(ModPrice::class)->create(['days' => 30, 'currency' => 'PLN']);

        $data = ['mod_price' => [
            'dsrf',
            [],
            ['id' => 0, 'checksum' => 'fdsf'],
            ['id' => $mod_price->id, 'checksum' => 'fdsf'],
        ]];

        $response = $this->post('packages?selected_company_id=' . $company->id, $data);

        $this->verifyResponseValidation(
            $response,
            [
            'mod_price.0.id',
            'mod_price.0.checksum',
            'mod_price.1.id',
            'mod_price.1.checksum',
            'mod_price.2.id',
            'mod_price.3.id',
        ],
            [
                'mod_price.2.checksum',
                'mod_price.3.checksum',
            ]
        );
    }

    /** @test */
    public function store_errorContentArray()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $mod_price_1 = factory(ModPrice::class)->create(['days' => 30, 'currency' => 'USD']);
        $mod_price_2 = factory(ModPrice::class)->create(['days' => 365, 'currency' => 'PLN']);
        $mod_price_3 = factory(ModPrice::class)->create(['days' => 30, 'currency' => 'PLN']);

        $data = [
            'package_id' => 0,
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price' => [
            ['id' => $mod_price_1->id, 'checksum' => 'fdsf'],
            ['id' => $mod_price_2->id, 'checksum' => 'fdsf'],
            ['id' => $mod_price_3->id, 'checksum' => 'fdsf'],
        ], ];

        $response = $this->post('packages?selected_company_id=' . $company->id, $data);

        $this->verifyResponseValidation(
            $response,
            [
            'mod_price.0.id',
            'mod_price.1.id',
        ],
            [
                'mod_price.0.checksum',
                'mod_price.1.checksum',
                'mod_price.2.checksum',
                'mod_price.2.id',
            ]
        );
    }

    /** @test */
    public function store_errorWrongChecksum()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);
        auth()->loginUsingId($this->user->id);

        $package_id = Package::where('slug', Package::PREMIUM)->first()->id;

        $mod_prices = [];

        ModPrice::where('package_id', $package_id)->where('days', 30)
            ->whereHas('moduleMod', function ($q) {
                $q->where('test', 0);
            })->get()->each(function ($mod_price) use (&$mod_prices) {
                $mod_prices [] = [
                    'id' => $mod_price->id,
                    'checksum' => Crypter::encrypt($mod_price->id, $mod_price->price),
                ];
            });

        $mod_prices[2]['checksum'] = Crypter::encrypt(0, 0);

        $data = [
            'package_id' => $package_id,
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price' => $mod_prices,
        ];

        $response = $this->post('packages?selected_company_id=' . $company->id, $data);

        $this->verifyResponseError($response, 409, ErrorCode::PACKAGE_DATA_CONSISTENCY_ERROR);
    }

    /** @test */
    public function store_errorWrongNoModPrice()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);
        auth()->loginUsingId($this->user->id);

        $package_id = Package::where('slug', Package::PREMIUM)->first()->id;

        $mod_prices = [];

        ModPrice::where('package_id', $package_id)->where('days', 30)
            ->whereHas('moduleMod', function ($q) {
                $q->where('test', 0);
            })->limit(10)->get()->each(function ($mod_price) use (&$mod_prices) {
                $mod_prices [] = [
                    'id' => $mod_price->id,
                    'checksum' => Crypter::encrypt($mod_price->id, $mod_price->price),
                ];
            });

        $data = [
            'package_id' => $package_id,
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price' => $mod_prices,
        ];

        $response = $this->post('packages?selected_company_id=' . $company->id, $data);

        $this->verifyResponseError($response, 409, ErrorCode::PACKAGE_DATA_CONSISTENCY_ERROR);
    }

    /** @test */
    public function store_success()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);
        auth()->loginUsingId($this->user->id);

        $package_id = Package::where('slug', Package::PREMIUM)->first()->id;

        $mod_prices_data = [];

        $mod_prices = ModPrice::where('package_id', $package_id)->where('days', 30)
            ->whereHas('moduleMod', function ($q) {
                $q->where('test', 0);
            })->get();

        foreach ($mod_prices as $mod_price) {
            $mod_price->update(['price' => 123]);
            $mod_prices_data [] = [
                'id' => $mod_price->id,
                'checksum' => Crypter::encrypt($mod_price->id, $mod_price->price),
            ];
        }

        $data = [
            'package_id' => $package_id,
            'days' => 30,
            'is_test' => 0,
            'currency' => 'PLN',
            'mod_price' => $mod_prices_data,
        ];

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $company_modules = CompanyModule::where('company_id', $company->id)->get();

        $json = $this->post('packages?selected_company_id=' . $company->id, $data)
            ->assertStatus(200)->json()['data'];

        $this->assertSame($count_payments, Payment::count() - 1);
        $this->assertSame($count_transactions, Transaction::count() - 1);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - count($mod_prices));

        $payment = Payment::orderByDesc('id')->first();

        $this->assertSame(Transaction::orderByDesc('id')->first()->id, $json['id']);
        $this->assertSame(1, count($json['payments']['data']));
        $this->assertSame(count($mod_prices) * 123, $json['payments']['data'][0]['price_total']);
        $this->assertSame($payment->id, $json['payments']['data'][0]['id']);

        foreach ($company_modules as $company_module) {
            $tmp = CompanyModule::where('company_id', $company->id)->where('module_id', $company_module->module_id)->first();
            $this->assertSame($tmp->value, $company_module->value);
        }

        foreach ($mod_prices as $mod_price) {
            $old_value = CompanyModule::where('company_id', $company->id)->where('module_id', $mod_price->moduleMod->module_id)->first()->value;
            $history = CompanyModuleHistory::where('transaction_id', $json['id'])
                ->where('company_id', $company->id)->where('module_mod_id', $mod_price->module_mod_id)->first();
            $this->assertSame($mod_price->moduleMod->module_id, $history->module_id);
            $this->assertSame($old_value, $history->old_value);
            $this->assertSame($mod_price->moduleMod->value, $history->new_value);
            $this->assertSame(null, $history->start_date);
            $this->assertSame(null, $history->expiration_date);
            $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history->status);
            $this->assertSame($mod_price->package_id, $history->package_id);
            $this->assertSame($mod_price->price, $history->price);
            $this->assertSame($mod_price->currency, $history->currency);
            $this->assertSame($mod_price->vat, $history->vat);
        }

        //payment
        $this->assertSame(count($mod_prices) * 123, $payment->price_total);
        $this->assertSame(count($mod_prices) * 23, $payment->vat);
        $this->assertSame($mod_prices[0]->currency, $payment->currency);
        $this->assertSame(null, $payment->external_order_id);
        $this->assertSame(PaymentStatus::STATUS_BEFORE_START, $payment->status);
        $this->assertSame(null, $payment->type);
        $this->assertSame(null, $payment->subscription_id);
        $this->assertSame(30, $payment->days);
        $this->assertSame(null, $payment->expiration_date);
        $this->assertSame($json['id'], $payment->transaction_id);
    }
}
