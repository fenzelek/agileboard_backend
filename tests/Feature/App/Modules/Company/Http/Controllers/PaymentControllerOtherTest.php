<?php

namespace  Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Models\Db\Company;
use App\Models\Db\CompanyModule;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;
use App\Models\Db\Package;
use App\Models\Db\Transaction;
use App\Modules\Company\Services\PayU\PayU;
use App\Modules\Company\Services\PayU\Response\ResponseOrderByCardFirst;
use App\Modules\Company\Services\PayU\Response\ResponseOrderSimply;
use Carbon\Carbon;
use Mockery as m;
use App\Helpers\ErrorCode;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;
use App\Models\Db\Payment;
use App\Models\Db\Subscription;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Other\PaymentStatus;

class PaymentControllerOtherTest extends TestCase
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
    public function index_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('companies/payments?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('companies/payments?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('companies/payments?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_CANCELED]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment->transaction->id,
        ]);
        $payment4 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_BEFORE_START]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment4->transaction->id,
        ]);
        $subscription = factory(Subscription::class)->create();
        $payment3 = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'subscription_id' => $subscription->id,
        ]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment3->transaction->id,
        ]);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_NEW]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment2->transaction->id,
        ]);

        $response = $this->get('companies/payments?limit=2&selected_company_id=' . $company->id);

        $response->assertStatus(200);
        $data = $response->decodeResponseJson()['data'];

        $this->assertSame(2, count($data));

        foreach ($data[0] as $key => $value) {
            if ($key == 'subscription') {
                $this->assertSame(null, $value['data']);
            } elseif ($key == 'created_at' || $key == 'updated_at') {
                $this->assertSame($payment[$key]->format('Y-m-d H:i:s'), $value);
            } else {
                $this->assertSame($payment[$key], $value);
            }
        }

        foreach ($data[1] as $key => $value) {
            if ($key == 'subscription') {
                $this->assertSame($subscription->id, $value['data']['id']);
            } elseif ($key == 'created_at' || $key == 'updated_at') {
                $this->assertSame($payment3[$key]->format('Y-m-d H:i:s'), $value);
            } else {
                $this->assertSame($payment3[$key], $value);
            }
        }
    }

    /** @test */
    public function index_successFilter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_CANCELED]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment->transaction->id,
        ]);
        $payment3 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment3->transaction->id,
        ]);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_NEW]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment2->transaction->id,
        ]);

        $response = $this->get('companies/payments?status=NEW&selected_company_id=' . $company->id);

        $response->assertStatus(200);
        $data = $response->decodeResponseJson()['data'];

        $this->assertSame(1, count($data));

        foreach ($data[0] as $key => $value) {
            if ($key == 'subscription') {
                $this->assertSame(null, $value['data']);
            } elseif ($key == 'created_at' || $key == 'updated_at') {
                $this->assertSame($payment2[$key]->format('Y-m-d H:i:s'), $value);
            } else {
                $this->assertSame($payment2[$key], $value);
            }
        }
    }

    /** @test */
    public function show_errorNotExit()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('companies/payments/0?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function show_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $data = $this->showPrepareData($company);

        $response = $this->get('companies/payments/' . $data['payment']->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function show_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $data = $this->showPrepareData($company);

        $response = $this->get('companies/payments/' . $data['payment']->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function show_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $data = $this->showPrepareData($company);

        $response = $this->get('companies/payments/' . $data['payment']->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function show_errorOtherCompany()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $otherCompany = factory(Company::class)->create();
        $data = $this->showPrepareData($otherCompany);

        $response = $this->get('companies/payments/' . $data['payment']->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function show_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_BEFORE_START]);
        $module = factory(Module::class)->create();
        $moduleMod = factory(ModuleMod::class)->create(['module_id' => $module->id]);
        $premiumPackage = Package::where('slug', Package::PREMIUM)->first();
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => $premiumPackage->id,
            'company_id' => $company->id,
            'transaction_id' => $payment->transaction->id,
            'module_id' => $module->id,
            'module_mod_id' => $moduleMod->id,
        ]);

        $this->get('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id)
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'subscription_id' => $payment->subscription_id,
                    'price_total' => $payment->price_total,
                    'currency' => $payment->currency,
                    'vat' => $payment->vat,
                    'external_order_id' => $payment->external_order_id,
                    'status' => $payment->status,
                    'type' => $payment->type,
                    'days' => $payment->days,
                    'expiration_date' => null,
                    'created_at' => $payment->created_at->toDateTimeString(),
                    'updated_at' => $payment->updated_at->toDateTimeString(),
                    'transaction' => [
                        'data' => [
                            'id' => $payment->transaction->id,
                            'created_at' => $payment->transaction->created_at->toDateTimeString(),
                            'updated_at' => $payment->transaction->updated_at->toDateTimeString(),
                            'company_modules_history' => [
                                'data' => [
                                    [
                                        'id' => $history->id,
                                        'company_id' => $history->company_id,
                                        'module_id' => $history->module_id,
                                        'module_mod_id' => $history->module_mod_id,
                                        'old_value' => $history->old_value,
                                        'new_value' => $history->new_value,
                                        'start_date' => $history->start_date,
                                        'expiration_date' => $history->expiration_date,
                                        'status' => $history->status,
                                        'package_id' => $history->package_id,
                                        'price' => (int) $history->price,
                                        'currency' => $history->currency,
                                        'vat' => $history->vat,
                                        'transaction_id' => $history->transaction_id,
                                        'created_at' => $history->created_at->toDateTimeString(),
                                        'updated_at' => $history->updated_at->toDateTimeString(),
                                        'module' => [
                                            'data' => [
                                                'id' => $module->id,
                                                'name' => $module->name,
                                                'slug' => $module->slug,
                                                'description' => $module->description,
                                                'visible' => (int) $module->visible,
                                                'available' => (int) $module->available,
                                            ],
                                        ],
                                        'module_mod' => [
                                            'data' => [
                                                'id' => $moduleMod->id,
                                                'module_id' => $moduleMod->module_id,
                                                'test' => (int) $moduleMod->test,
                                                'value' => (string) $moduleMod->value,
                                            ],
                                        ],
                                        'package' => [
                                            'data' => [
                                                'id' => $premiumPackage->id,
                                                'name' => $premiumPackage->name,
                                                'slug' => $premiumPackage->slug,
                                                'default' => (int) $premiumPackage->default,
                                                'portal_name' => $premiumPackage->portal_name,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function confirmBuy_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create();

        $response = $this->post('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function confirmBuy_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create();

        $response = $this->post('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function confirmBuy_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create();

        $response = $this->post('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function confirmBuy_errorPaymentNotExist()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('companies/payments/0?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function confirmBuy_errorPaymentWrongStatus()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_NEW]);

        $response = $this->post('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function confirmBuy_errorPaymentOtherCompany()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_BEFORE_START]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => 0,
            'transaction_id' => $payment->transaction->id,
        ]);

        $response = $this->post('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function confirmBuy_errorEmptyData()
    {
        $this->prepareDbDataConfirmBuy();

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id);

        $this->verifyResponseValidation(
            $response,
            ['subscription'],
            ['token', 'card_exp_month', 'card_exp_year', 'card_cvv', 'card_number']
        );
    }

    /** @test */
    public function confirmBuy_errorWithoutCardData()
    {
        $this->prepareDbDataConfirmBuy();

        $data = [
            'subscription' => false,
            'type' => Payment::TYPE_CARD,
        ];

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id, $data);

        $this->verifyResponseValidation(
            $response,
            ['card_exp_month', 'card_exp_year', 'card_cvv', 'card_number'],
            ['subscription', 'token']
        );
    }

    /** @test */
    public function confirmBuy_errorCentSelectSubscription()
    {
        $this->prepareDbDataConfirmBuy();

        $data = [
            'subscription' => true,
            'type' => Payment::TYPE_SIMPLE,
        ];

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id, $data);

        $this->verifyResponseValidation(
            $response,
            ['subscription']
        );
    }

    /** @test */
    public function confirmBuy_errorWrongDataCardV1()
    {
        $this->prepareDbDataConfirmBuy();

        $data = [
            'subscription' => false,
            'type' => Payment::TYPE_CARD,
            'card_exp_month' => 'asd',
            'card_exp_year' => 'asd',
            'card_cvv' => 'asd',
            'card_number' => '12345678901',
        ];

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id, $data);

        $this->verifyResponseValidation(
            $response,
            ['card_exp_month', 'card_exp_year', 'card_cvv', 'card_number'],
            ['subscription', 'token']
        );
    }

    /** @test */
    public function confirmBuy_errorWrongDataCardV2()
    {
        $this->prepareDbDataConfirmBuy();

        $data = [
            'subscription' => false,
            'type' => Payment::TYPE_CARD,
            'card_exp_month' => '1',
            'card_exp_year' => '123',
            'card_cvv' => '12',
            'card_number' => '12345678901234567890',
        ];

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id, $data);

        $this->verifyResponseValidation(
            $response,
            ['card_exp_month', 'card_exp_year', 'card_cvv', 'card_number'],
            ['subscription', 'token']
        );
    }

    /** @test */
    public function confirmBuy_errorPayuProblems()
    {
        $this->prepareDbDataConfirmBuy(false);

        $data = [
            'subscription' => false,
            'type' => Payment::TYPE_SIMPLE,
        ];

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id, $data);
        $this->verifyResponseError($response, 409, ErrorCode::PAYU_TECHNICAL_PROBLEMS);
    }

    /** @test */
    public function confirmBuy_successSimply()
    {
        $result = m::mock(ResponseOrderSimply::class);
        $result->shouldReceive('isSuccess')->andReturn(true);
        $result->shouldReceive('getRedirectUrl')->andReturn('http://example.com');

        $this->prepareDbDataConfirmBuy($result);

        $data = [
            'subscription' => false,
            'type' => Payment::TYPE_SIMPLE,
        ];

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id, $data);
        $response->assertStatus(200);
        $data = $response->decodeResponseJson()['data'];

        $this->assertSame('http://example.com', $data['redirect_url']);
    }

    /** @test */
    public function confirmBuy_successExtendModule()
    {
        $result = m::mock(ResponseOrderSimply::class);
        $result->shouldReceive('isSuccess')->andReturn(true);
        $result->shouldReceive('getRedirectUrl')->andReturn('http://example.com');

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('setParams');
        $payu->shouldReceive('setUser');
        $payu->shouldReceive('createOrder')->andReturn($result);

        $this->app->instance(PayU::class, $payu);

        $this->createUser();
        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(365));
        auth()->loginUsingId($this->user->id);
        $subscription = factory(Subscription::class)->create();
        CompanyModule::whereNotNull('package_id')->where('company_id', $this->company->id)->update([
            'subscription_id' => $subscription->id,
        ]);

        $this->payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_BEFORE_START]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $this->company->id,
            'transaction_id' => $this->payment->transaction->id,
        ]);

        $data = [
            'subscription' => true,
            'type' => Payment::TYPE_SIMPLE,
        ];

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id, $data);
        $response->assertStatus(200);
        $data = $response->decodeResponseJson()['data'];

        $this->assertSame('http://example.com', $data['redirect_url']);
    }

    /** @test */
    public function confirmBuy_warning3ds()
    {
        $result = m::mock(ResponseOrderByCardFirst::class);
        $result->shouldReceive('isSuccess')->andReturn(false);
        $result->shouldReceive('getRedirectUrl')->andReturn('http://example.com');
        $result->shouldReceive('getError')->andReturn(ResponseOrderByCardFirst::WARNING_CONTINUE_3DS);

        $this->prepareDbDataConfirmBuy($result);

        $data = [
            'subscription' => false,
            'type' => Payment::TYPE_CARD,
            'card_exp_month' => '10',
            'card_exp_year' => '2030',
            'card_cvv' => '122',
            'card_number' => '12345678901234',
        ];

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id, $data);
        $this->verifyResponseError($response, 409, ErrorCode::PAYU_WARNING_CONTINUE_3DS, ['redirect_url']);

        $data = $response->decodeResponseJson()['fields'];
        $this->assertSame('http://example.com', $data['redirect_url']);
    }

    /** @test */
    public function confirmBuy_warningCvv()
    {
        $result = m::mock(ResponseOrderByCardFirst::class);
        $result->shouldReceive('isSuccess')->andReturn(false);
        $result->shouldReceive('getError')->andReturn(ResponseOrderByCardFirst::WARNING_CONTINUE_CVV);

        $this->prepareDbDataConfirmBuy($result);

        $data = [
            'subscription' => false,
            'type' => Payment::TYPE_SIMPLE,
        ];

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id, $data);
        $this->verifyResponseError($response, 409, ErrorCode::PAYU_WARNING_CONTINUE_CVV);
    }

    /** @test */
    public function confirmBuy_someError()
    {
        $result = m::mock(ResponseOrderByCardFirst::class);
        $result->shouldReceive('isSuccess')->andReturn(false);
        $result->shouldReceive('getError')->andReturn('asdasdasd');

        $this->prepareDbDataConfirmBuy($result);

        $data = [
            'subscription' => false,
            'type' => Payment::TYPE_CARD,
            'token' => encrypt([
                'id' => $this->user->id,
                'token' => 'asd',
            ]),
        ];

        $response = $this->post('companies/payments/' . $this->payment->id . '?selected_company_id=' . $this->company->id, $data);
        $this->verifyResponseError($response, 409, ErrorCode::PAYU_SOME_ERROR);
    }

    public function prepareDbDataConfirmBuy($payuResponse = null)
    {
        $this->createUser();
        $this->company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $this->payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_BEFORE_START]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $this->company->id,
            'transaction_id' => $this->payment->transaction->id,
        ]);

        if ($payuResponse !== null) {
            $payu = m::mock(PayU::class);
            $payu->shouldReceive('setParams');
            $payu->shouldReceive('setUser');
            $payu->shouldReceive('createOrder')->andReturn($payuResponse);

            $this->app->instance(PayU::class, $payu);
        }
    }

    /** @test */
    public function cardList_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('companies/payments/cards?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cardList_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('companies/payments/cards?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cardList_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('companies/payments/cards?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cardList_errorPayU()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('setUser');
        $payu->shouldReceive('getCardTokens')->andReturn(false);

        $this->app->instance(PayU::class, $payu);

        $response = $this->get('companies/payments/cards?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 409, ErrorCode::PAYU_TECHNICAL_PROBLEMS);
    }

    /** @test */
    public function cardList_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $cards = [
            (object) [
                 'cardNumberMasked' => '411111******1111',
                 'value' => 'TOKC_XATB7DF8ACXYTVQIPLWTVPFRKQE',
                 'status' => 'ACTIVE',
            ],
            (object) [
                'cardNumberMasked' => '411111******11112',
                'value' => 'TOKC_XATB7DF8ACXYTVQIPLWTVPFRKQW',
                'status' => 'EXPIRED',
            ],
        ];

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('setUser');
        $payu->shouldReceive('getCardTokens')->andReturn($cards);

        $this->app->instance(PayU::class, $payu);

        $response = $this->get('companies/payments/cards?selected_company_id=' . $company->id);
        $response->assertStatus(200);
        $data = $response->decodeResponseJson()['data'];

        $this->assertSame(1, count($data));
        $this->assertSame('411111******1111', $data[0]['cardNumberMasked']);
        $encrypt = decrypt($data[0]['value']);
        $this->assertSame($this->user->id, $encrypt['id']);
        $this->assertSame('TOKC_XATB7DF8ACXYTVQIPLWTVPFRKQE', $encrypt['token']);
    }

    /** @test */
    public function cancelSubscription_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $subscription = factory(Subscription::class)->create();

        $response = $this->delete('companies/payments/subscription/' . $subscription->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelSubscription_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $subscription = factory(Subscription::class)->create();

        $response = $this->delete('companies/payments/subscription/' . $subscription->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelSubscription_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $subscription = factory(Subscription::class)->create();

        $response = $this->delete('companies/payments/subscription/' . $subscription->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelSubscription_errorNoActive()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $subscription = factory(Subscription::class)->create(['active' => false]);

        $response = $this->delete('companies/payments/subscription/' . $subscription->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelSubscription_errorDBError()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $subscription = factory(Subscription::class)->create(['active' => true]);

        $response = $this->delete('companies/payments/subscription/' . $subscription->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelSubscription_errorWrongCompany()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $subscription = factory(Subscription::class)->create(['active' => true]);
        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_BEFORE_START,
            'subscription_id' => $subscription->id,
        ]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => 0,
            'transaction_id' => $payment->transaction->id,
        ]);

        $response = $this->delete('companies/payments/subscription/' . $subscription->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelSubscription_success_extendModule()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDay());
        auth()->loginUsingId($this->user->id);

        $module_extend = Module::findBySlug('test.extend.module');
        $mod = $module_extend->mods()->where('value', '')->first();
        $subscription_extend = factory(Subscription::class)->create(['repeats' => 14]);
        $companyModule_extend = CompanyModule::where('company_id', $company->id)->where('module_id', $module_extend->id)->first();
        $companyModule_extend->update([
            'expiration_date' => Carbon::now()->subDay(),
            'subscription_id' => $subscription_extend->id,
            'value' => 'test1',
        ]);

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_BEFORE_START,
            'subscription_id' => $subscription_extend->id,
        ]);

        CompanyModuleHistory::where('company_id', $company->id)->where('module_id', $module_extend->id)->update([
            'transaction_id' => $payment->transaction->id,
        ]);

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $response = $this->delete('companies/payments/subscription/' . $subscription_extend->id . '?selected_company_id=' . $company->id);
        $response->assertStatus(200);

        $new_subscription = $subscription_extend->fresh();
        $this->assertSame($subscription_extend->id, $new_subscription->id);
        $this->assertEquals(false, $new_subscription->active);

        $this->assertSame($count_payments, Payment::count());
        $this->assertSame($count_transactions, Transaction::count());
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        $companyModule = CompanyModule::where('company_id', $company->id)->where('module_id', $module_extend->id)->first();
        $this->assertSame($companyModule_extend->value, $companyModule->value);
        $this->assertSame($companyModule_extend->package_id, $companyModule->package_id);
        $this->assertSame($companyModule_extend->expiration_date->toDateTimeString(), $companyModule->expiration_date->toDateTimeString());
        $this->assertSame($companyModule_extend->subscription_id, $companyModule->subscription_id);
    }

    /** @test */
    public function cancelSubscription_success_packageWithExtendModule()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDay());
        auth()->loginUsingId($this->user->id);

        $subscription_package = factory(Subscription::class)->create([
            'repeats' => 14,
            'user_id' => $this->user->id,
            'days' => 30,
        ]);

        $payment = factory(Payment::class)->create([
            'subscription_id' => $subscription_package->id,
            'days' => $subscription_package->days,
            'status' => PaymentStatus::STATUS_COMPLETED,
        ]);

        CompanyModule::where('company_id', $company->id)->whereNotNull('package_id')->update([
            'subscription_id' => $subscription_package->id,
        ]);

        CompanyModuleHistory::where('company_id', $company->id)->whereNotNull('package_id')->update([
            'transaction_id' => $payment->transaction->id,
        ]);

        $module_extend = Module::findBySlug('test.extend.module');
        $mod = $module_extend->mods()->where('value', '')->first();

        $subscription_extend = factory(Subscription::class)->create(['repeats' => 14]);
        $companyModule_extend = CompanyModule::where('company_id', $company->id)->where('module_id', $module_extend->id)->first();
        $companyModule_extend->update([
            'expiration_date' => Carbon::now()->subDay(),
            'subscription_id' => $subscription_extend->id,
            'value' => 'test1',
        ]);

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();
        $all_companyModules = CompanyModule::where('company_id', $company->id)->get();

        $response = $this->delete('companies/payments/subscription/' . $subscription_package->id . '?selected_company_id=' . $company->id);
        $response->assertStatus(200);

        $subscription_package = $subscription_package->fresh();
        $subscription_extend = $subscription_extend->fresh();

        $this->assertSame(0, $subscription_package->active);
        $this->assertSame(0, $subscription_extend->active);

        $this->assertSame($count_payments, Payment::count());
        $this->assertSame($count_transactions, Transaction::count());
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        foreach ($all_companyModules as $module) {
            $new = CompanyModule::where('company_id', $company->id)->findOrFail($module->id);
            $this->assertSame($module->company_id, $new->company_id);
            $this->assertSame($module->module_id, $new->module_id);
            $this->assertSame($module->value, $new->value);
            $this->assertSame($module->package_id, $new->package_id);
            $this->assertSame($module->expiration_date->toDateTimeString(), $new->expiration_date->toDateTimeString());
            $this->assertSame($module->subscription_id, $new->subscription_id);
        }
    }

    /** @test */
    public function cancelPayment_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create();

        $response = $this->delete('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelPayment_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create();

        $response = $this->delete('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelPayment_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create();

        $response = $this->delete('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelPayment_errorPaymentNotExist()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $response = $this->delete('companies/payments/0?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function cancelPayment_errorPaymentWrongStatus()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_NEW]);

        $response = $this->delete('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelPayment_errorPaymentOtherCompany()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => 0,
            'transaction_id' => $payment->transaction->id,
        ]);

        $response = $this->delete('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function cancelPayment_errorFromPayu()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment->transaction->id,
        ]);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('cancel')->andReturn(false);
        $this->app->instance(PayU::class, $payu);

        $response = $this->delete('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 409, ErrorCode::PAYU_SOME_ERROR);
    }

    /** @test */
    public function cancelPayment_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment->transaction->id,
        ]);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('cancel')->andReturn(true);
        $this->app->instance(PayU::class, $payu);

        $response = $this->delete('companies/payments/' . $payment->id . '?selected_company_id=' . $company->id);
        $response->assertStatus(200);
    }

    /** @test */
    public function payAgain_errorNoPermissionForClient()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $transaction = factory(Transaction::class)->create();

        $response = $this->post('companies/payments/again/' . $transaction->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function payAgain_errorNoPermissionForDeveloper()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $transaction = factory(Transaction::class)->create();

        $response = $this->post('companies/payments/again/' . $transaction->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function payAgain_errorNoPermissionForAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $transaction = factory(Transaction::class)->create();

        $response = $this->post('companies/payments/again/' . $transaction->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function payAgain_errorPaymentNotExist()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $response = $this->post('companies/payments/again/0?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function payAgain_errorPaymentWrongStatus()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_NEW]);

        $response = $this->post('companies/payments/again/' . $payment->transaction->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function payAgain_errorPaymentOtherCompany()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_BEFORE_START]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => 0,
            'transaction_id' => $payment->transaction->id,
        ]);

        $response = $this->post('companies/payments/again/' . $payment->transaction->id . '?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function payAgain_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_CANCELED]);
        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_CANCELED,
            'transaction_id' => $payment->transaction->id,
        ]);
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment->transaction->id,
        ]);

        $count = Payment::count();

        $response = $this->post('companies/payments/again/' . $payment->transaction->id . '?selected_company_id=' . $company->id);
        $response->assertStatus(200);

        $this->assertSame($count + 1, Payment::count());
        $payment_new = Payment::orderByDesc('id')->first();

        $response->assertStatus(200)->assertJsonFragment([
            'data' => [
                'id' => $payment_new->id,
                'transaction_id' => $payment_new->transaction_id,
                'subscription_id' => $payment_new->subscription_id,
                'price_total' => $payment_new->price_total,
                'currency' => $payment_new->currency,
                'vat' => $payment_new->vat,
                'external_order_id' => $payment_new->external_order_id,
                'status' => $payment_new->status,
                'type' => $payment_new->type,
                'days' => $payment_new->days,
                'expiration_date' => null,
                'created_at' => $payment_new->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $payment_new->updated_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    private function showPrepareData($company)
    {
        $data = [];
        $data['payment'] = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_BEFORE_START]);
        $data['history'] = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $data['payment']->transaction->id,
        ]);

        return $data;
    }
}
