<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers\ReceiptController;

use App\Helpers\ErrorCode;
use App\Models\Db\ErrorLog;
use App\Models\Db\Package;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\ReceiptItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\Receipt;
use App\Models\Db\PaymentMethod;
use App\Models\Db\VatRate;
use App\Models\Db\CompanyService;
use Tests\BrowserKitTestCase;
use Tests\Helpers\CompanyTokenCreator;

class StoreTest extends BrowserKitTestCase
{
    use DatabaseTransactions, CompanyTokenCreator;

    /**
     * @var Carbon
     */
    protected $now;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);
    }

    /** @test */
    public function store_user_has_permission()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $this->post(
            'receipts',
            [],
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog([], $company);
    }

    /** @test */
    public function store_it_returns_validation_error_without_data()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $this->post(
            'receipts',
            [],
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'transaction_number',
            'sale_date',
            'price_net',
            'price_gross',
            'vat_sum',
            'payment_method',
            'number',
            'items',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog([], $company);
    }

    /** @test */
    public function store_it_returns_validation_error_duplicate_transaction_number()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $transaction_number = '1234567890';
        $receipt = factory(Receipt::class)->create();
        $receipt->company_id = $company->id;
        $receipt->transaction_number = $transaction_number;
        $receipt->save();

        $payment_method = factory(PaymentMethod::class)->create();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);
        $incoming_data['transaction_number'] = $transaction_number;

        $this->post(
            'receipts',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        );

        $this->verifyErrorResponse(420, ErrorCode::OTHER_SALES_DUPLICATE_TRANSACTION_NUMBER);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($incoming_data, $company);
    }

    /** @test */
    public function store_it_return_validation_error_empty_items()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $transaction_number = '1234567890';
        $data_incoming = [
            'items' => [[]],
        ];
        $receipt = factory(Receipt::class)->create();

        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.name',
            'items.0.price_net',
            'items.0.price_net_sum',
            'items.0.price_gross',
            'items.0.price_gross_sum',
            'items.0.vat_rate',
            'items.0.vat_sum',
            'items.0.quantity',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_payment_method()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $payment_method = factory(PaymentMethod::class)->create();
        $fake_payment_method = $payment_method->slug;
        PaymentMethod::destroy($payment_method->id);

        $data_incoming = [
            'payment_method' => $fake_payment_method,
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_method',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_var_rate()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $fake_rate_name = $vat_rate->name;
        VatRate::destroy($vat_rate->id);

        $data_incoming = [
            'items' => [
                [
                    'vat_rate' => $fake_rate_name,
                ],
            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'items.0.vat_rate',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_sale_date()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $fake_rate_name = $vat_rate->name;

        $data_incoming = [
            'sale_date' => 'no_date_string_format',
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'sale_date',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_numeric_fields()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $data_incoming = [
            'price_net' => 'not_numeric',
            'price_gross' => 'not_numeric',
            'vat_sum' => 'not_numeric',
            'items' => [
                [
                    'price_net' => 'not_numeric',
                    'price_net_sum' => 'not_numeric',
                    'price_gross' => 'not_numeric',
                    'price_gross_sum' => 'not_numeric',
                    'vat_sum' => 'not_numeric',
                ],
            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'price_net',
            'price_gross',
            'vat_sum',
            'items.0.price_net',
            'items.0.price_net_sum',
            'items.0.price_gross',
            'items.0.price_gross_sum',
            'items.0.vat_sum',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_payment_method_types()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $data_incoming = [
            'payment_method_types' => [
                [
                'type' => PaymentMethodType::OTHER,
                'amount' => 'no_valid_amount',
                ],
            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_method_types.0.type',
            'payment_method_types.0.amount',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_payment_method_mix_cash_card()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $data_incoming = [];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_method_types',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_payment_method_mix_cash_card_lack_types()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $data_incoming = [
            'payment_method' => PaymentMethodType::CASH_CARD,
            'payment_method_types' => [

            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_method_types.0.type',
            'payment_method_types.1.type',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_payment_method_mix_cash_card_duplicate_card_types()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $data_incoming = [
            'payment_method' => PaymentMethodType::CASH_CARD,
            'payment_method_types' => [
                [
                    'type' => PaymentMethodType::DEBIT_CARD,
                ],
                [
                    'type' => PaymentMethodType::DEBIT_CARD,
                ],

            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_method_types.0.type',
            'payment_method_types.0.amount',
            'payment_method_types.1.amount',
            'payment_method_types.1.type',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_payment_method_mix_cash_card_incoming_other_type()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $data_incoming = [
            'payment_method' => PaymentMethodType::CASH_CARD,
            'payment_method_types' => [
                [
                    'type' => PaymentMethodType::BANK_TRANSFER,
                ],
                [
                    'type' => PaymentMethodType::OTHER,
                ],

            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_method_types.0.type',
            'payment_method_types.0.amount',
            'payment_method_types.1.amount',
            'payment_method_types.1.type',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_amount_to_much()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $data_incoming = [
            'payment_method' => PaymentMethodType::CASH_CARD,
            'price_net' => 100000000,
            'price_gross' => 100000000,
            'vat_sum' => 100000000,
            'items' => [
                [
                    'price_net' => 100000000,
                    'price_net_sum' => 100000000,
                    'price_gross' => 100000000,
                    'price_gross_sum' => 100000000,
                    'vat_sum' => 100000000,
                ],
            ],
            'payment_method_types' => [
                [
                    'type' => PaymentMethodType::DEBIT_CARD,
                    'amount' => 100000000,
                ],
                [
                    'type' => PaymentMethodType::CASH,
                    'amount' => 100000000,
                ],
            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'price_net',
            'price_gross',
            'vat_sum',
            'items.0.price_net',
            'items.0.price_net_sum',
            'items.0.price_gross',
            'items.0.price_gross_sum',
            'items.0.vat_sum',
            'payment_method_types.0.amount',
            'payment_method_types.1.amount',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_amount_to_low()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $data_incoming = [
            'payment_method' => PaymentMethodType::CASH_CARD,
            'price_net' => 0,
            'price_gross' => 0,
            'vat_sum' => -0.01,
            'items' => [
                [
                    'price_net' => 0,
                    'price_net_sum' => 0,
                    'price_gross' => 0,
                    'price_gross_sum' => 0,
                    'vat_sum' => -0.01,
                ],
            ],
            'payment_method_types' => [
                [
                    'type' => PaymentMethodType::DEBIT_CARD,
                    'amount' => 0,
                ],
                [
                    'type' => PaymentMethodType::CASH,
                    'amount' => 0,
                ],
            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'price_net',
            'price_gross',
            'vat_sum',
            'items.0.price_net',
            'items.0.price_net_sum',
            'items.0.price_gross',
            'items.0.price_gross_sum',
            'items.0.vat_sum',
            'payment_method_types.0.amount',
            'payment_method_types.1.amount',

        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_it_return_validation_error_integer_fields()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $data_incoming = [
            'items' => [
                [
                    'quantity' => 'not_integer',
                ],
            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'items.0.quantity',
        ]);

        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);

        $data_incoming = [
            'items' => [
                [
                    'quantity' => 99.99,
                ],
            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'items.0.quantity',
        ]);

        $this->assertEquals(2, ErrorLog::count());
    }

    /** @test */
    public function store_it_return_validation_error_to_long_fields()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $data_incoming = [
            'transaction_number' => '1234567890123456789012345678901234567890123456789012345678901234567890',
            'number' => '1234567890123456789012345678901234567890123456789012345678901234567890',
            'items' => [
                [
                    'vat_rate' => '1234567890123456789012345678901234567890123456789012345678901234567890',
                ],
            ],
        ];
        $this->post(
            'receipts',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'transaction_number',
            'number',
            'items.0.vat_rate',
        ]);
        $this->assertEquals(1, ErrorLog::count());
        $this->verifyErrorLog($data_incoming, $company);
    }

    /** @test */
    public function store_validation_passing()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        $this->post(
            'receipts',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201);
        $this->assertEquals(0, ErrorLog::count());
    }

    /** @test */
    public function store_new_service_was_added_to_company_services()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        CompanyService::whereRaw('1 = 1')->delete();
        $initials_company_service_amount = CompanyService::count();

        $vat_rate = new VatRate();

        $this->post(
            'receipts',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201);

        $this->assertSame($initials_company_service_amount + 3, CompanyService::count());

        $last_company_service = CompanyService::latest('id')->first();

        $last_incoming_company_service =
            $incoming_data['items'][count($incoming_data['items']) - 1];

        $this->assertSame($company->id, $last_company_service->company_id);
        $this->assertSame($last_incoming_company_service['name'], $last_company_service->name);
        $this->assertEmpty($last_company_service->pkwiu);
        $this->assertSame(
            $vat_rate->findByName($last_incoming_company_service['vat_rate'])->id,
            $last_company_service->vat_rate_id
        );
        $this->assertSame(2, $last_company_service->is_used);
        $this->assertSame(auth()->user()->id, $last_company_service->creator_id);
        $this->assertFalse((bool) $last_company_service->editor_id);
        $this->assertNotNull($last_company_service->created_at);
        $this->assertNotNull($last_company_service->updated_at);
        $this->assertEquals(0, ErrorLog::count());
    }

    /** @test */
    public function store_service_was_not_added_if_exists()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        CompanyService::whereRaw('1 = 1')->delete();

        $company_service = factory(CompanyService::class)->create();
        $company_service->name = 'service_1';
        $company_service->company_id = $company->id;
        $company_service->vat_rate_id = $vat_rate->id;
        $company_service->save();

        $initials_company_service_amount = CompanyService::count();

        $this->post(
            'receipts',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201);

        $this->assertSame($initials_company_service_amount + 2, CompanyService::count());

        $company_service_initials = $company_service->fresh();

        $this->assertSame($company->id, $company_service_initials->company_id);
        $this->assertSame($company_service->name, $company_service_initials->name);
        $this->assertSame($company_service->pkwiu, $company_service_initials->pkwiu);
        $this->assertSame(
            $company_service->vat_rate_id,
            $company_service_initials->vat_rate_id
        );
        $this->assertSame(1, $company_service_initials->is_used);
        $this->assertSame((int) false, $company_service_initials->creator_id);
        $this->assertFalse((bool) $company_service_initials->editor_id);
        $this->assertNotNull($company_service_initials->created_at);
        $this->assertNotNull($company_service_initials->updated_at);

        $this->assertEquals(0, ErrorLog::count());
    }

    /** @test */
    public function store_receipt_was_add_to_database()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        Receipt::whereRaw('1 = 1')->delete();

        $initials_receipt_amount = Receipt::count();

        $this->post(
            'receipts',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201);

        $this->assertSame($initials_receipt_amount + 1, Receipt::count());

        $receipt = Receipt::latest('id')->first();

        $this->assertSame($incoming_data['number'], $receipt->number);
        $this->assertSame(
            $incoming_data['transaction_number'],
            $receipt->transaction_number
        );
        $this->assertSame(auth()->id(), $receipt->user_id);
        $this->assertSame($company->id, $receipt->company_id);
        $this->assertSame($incoming_data['sale_date'], $receipt->sale_date);
        $this->assertSame(20054, $receipt->price_net);
        $this->assertSame(24666, $receipt->price_gross);
        $this->assertSame(4677, $receipt->vat_sum);
        $this->assertSame($payment_method->id, $receipt->payment_method_id);
        $this->assertNotNull($receipt->created_at);
        $this->assertNotNull($receipt->updated_at);

        $this->assertEquals(0, ErrorLog::count());
    }

    /** @test */
    public function store_receipt_items_was_added_to_database()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        $initials_receipt_items_amount = ReceiptItem::count();

        $this->post(
            'receipts',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201);

        $this->assertSame(
            $initials_receipt_items_amount + count($incoming_data['items']),
            ReceiptItem::count()
        );

        $receipt = Receipt::latest('id')->first();
        $receipt_items = ReceiptItem::latest('id')->limit(4)->get();
        $company_services = CompanyService::latest('id')->limit(3)->get();

        $expectationData = $this->expectationReceiptItemsData($receipt_items, $company_services);

        $item = count($incoming_data['items']);
        foreach ($expectationData as $expectation) {
            $this->assertSame(
                $incoming_data['items'][--$item]['name'],
                $expectation['receipt_item']->name
            );
            $this->assertSame($expectation['creator_id'], $expectation['receipt_item']->creator_id);
            $this->assertSame($expectation['price_net'], $expectation['receipt_item']->price_net);
            $this->assertSame(
                $expectation['price_net_sum'],
                $expectation['receipt_item']->price_net_sum
            );
            $this->assertSame(
                $expectation['price_gross'],
                $expectation['receipt_item']->price_gross
            );
            $this->assertSame(
                $expectation['price_gross_sum'],
                $expectation['receipt_item']->price_gross_sum
            );
            $this->assertSame($expectation['vat_sum'], $expectation['receipt_item']->vat_sum);
            $this->assertSame(
                $incoming_data['items'][$item]['vat_rate'],
                $expectation['receipt_item']->vat_rate
            );
            $this->assertSame($vat_rate->id, $expectation['receipt_item']->vat_rate_id);
            $this->assertSame(
                $incoming_data['items'][$item]['quantity'],
                $expectation['receipt_item']->quantity
            );
            $this->assertSame($receipt->id, $expectation['receipt_item']->receipt_id);
            $this->assertSame(
                $expectation['company_service']->id,
                $expectation['receipt_item']->company_service_id
            );
            $this->assertSame(
                $expectation['company_service']->name,
                $expectation['receipt_item']->name
            );
            $this->assertFalse((bool) $expectation['receipt_item']->editor_id);
            $this->assertNotNull($expectation['receipt_item']->created_at);
            $this->assertNotNull($expectation['receipt_item']->updated_at);
        }

        $this->assertEquals(0, ErrorLog::count());
    }

    /** @test */
    public function store_company_services_was_used()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        CompanyService::whereRaw('1 = 1')->delete();

        $this->post(
            'receipts',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201);

        $company_services = CompanyService::all();
        $company_services->each(function ($service) {
            $this->assertTrue((bool) $service->is_used);
        });

        $this->assertEquals(0, ErrorLog::count());
    }

    /** @test */
    public function store_response_structure()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        $this->post(
            'receipts',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201)
            ->seeJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'transaction_number',
                    'user_id',
                    'company_id',
                    'sale_date',
                    'price_net',
                    'price_gross',
                    'vat_sum',
                    'cash_back',
                    'payment_method_id',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertEquals(0, ErrorLog::count());
    }

    /** @test */
    public function store_response_data_correct()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create(['slug' => PaymentMethodType::CASH_CARD]);
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        $this->post(
            'receipts',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201);

        $json = $this->decodeResponseJson()['data'];
        $receipt = Receipt::latest('id')->first();
        $this->assertSame($receipt->id, $json['id']);
        $this->assertSame($receipt->number, $json['number']);
        $this->assertSame($receipt->transaction_number, $json['transaction_number']);
        $this->assertSame($receipt->user_id, $json['user_id']);
        $this->assertSame($receipt->sale_date, $json['sale_date']);
        $this->assertSame(200.54, $json['price_net']);
        $this->assertSame(246.66, $json['price_gross']);
        $this->assertSame(46.77, $json['vat_sum']);
        $this->assertSame(53.34, $json['cash_back']);
        $this->assertSame($receipt->payment_method_id, $json['payment_method_id']);
        $this->assertSame($receipt->created_at->toDateTimeString(), $json['created_at']);
        $this->assertSame($receipt->updated_at->toDateTimeString(), $json['updated_at']);

        $this->assertEquals(0, ErrorLog::count());
    }

    protected function verifyErrorLog($data, $company)
    {
        $error = ErrorLog::first();
        $this->assertSame($company->id, $error->company_id);
        $this->assertSame($this->user->id, $error->user_id);
        if (! empty($data['transaction_number'])) {
            $this->assertSame($data['transaction_number'], $error->transaction_number);
        }
        $this->assertSame($this->app->url->full(), $error->url);
        $this->assertSame('POST', $error->method);
        $this->assertStringContainsString(json_encode($data), $error->request);
        $this->assertSame($this->response->status(), $error->status_code);
        $this->assertSame($this->response->content(), $error->response);
        $this->assertSame(Carbon::now()->toDateTimeString(), $error->request_date);
        $this->assertSame(Carbon::now()->toDateTimeString(), $error->created_at->toDateTimeString());
    }

    protected function login_user_and_return_company_with_his_employee_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        list($token, $api_token) = $this->createCompanyTokenForUser(
            $this->user,
            $company,
            RoleType::API_USER
        );

        return [$company, $api_token];
    }

    protected function store_init_incoming_data(
        PaymentMethod $payment_method,
        VatRate $vat_rate
    ) {
        return [
            'transaction_number' => '1234567890',
            'sale_date' => Carbon::now()->toDateTimeString(),
            'price_net' => 200.54,
            'price_gross' => 246.66,
            'vat_sum' => 46.77,
            'payment_method' => $payment_method->slug,
            'number' => '1234567890',
            'items' => [
                [
                    'name' => 'service_1',
                    'price_net' => 10.00,
                    'price_net_sum' => 100.00,
                    'price_gross' => 12.30,
                    'price_gross_sum' => 123.00,
                    'vat_rate' => $vat_rate->name,
                    'vat_sum' => 23.00,
                    'quantity' => 10,
                ],
                [
                    'name' => 'service_2',
                    'price_net' => 20.1,
                    'price_net_sum' => 100,
                    'price_gross' => 24.6,
                    'price_gross_sum' => 123,
                    'vat_rate' => $vat_rate->name,
                    'vat_sum' => 0,
                    'quantity' => 5,
                ],
                [
                    'name' => 'service_same',
                    'price_net' => 10.45,
                    'price_net_sum' => 500,
                    'price_gross' => 11.1,
                    'price_gross_sum' => 333.33,
                    'vat_rate' => $vat_rate->name,
                    'vat_sum' => 33.33,
                    'quantity' => 5,
                ],
                [
                    'name' => 'service_same',
                    'price_net' => 30.333,
                    'price_net_sum' => 300.202,
                    'price_gross' => 33.404,
                    'price_gross_sum' => 333.993,
                    'vat_rate' => $vat_rate->name,
                    'vat_sum' => 66.663,
                    'quantity' => 10,
                ],
            ],
            'payment_method_types' => [
                [
                    'type' => PaymentMethodType::CASH,
                    'amount' => 100,
                ],
                [
                    'type' => PaymentMethodType::DEBIT_CARD,
                    'amount' => 200,
                ],
            ],
        ];
    }

    protected function expectationReceiptItemsData(Collection $receipt_items, Collection $company_services)
    {
        return [
            [
                'receipt_item' => $receipt_items->shift(),
                'company_service' => ($company_service = $company_services->shift()),
                'creator_id' => auth()->id(),
                'price_net' => 3033,
                'price_net_sum' => 30020,
                'price_gross' => 3340,
                'price_gross_sum' => 33399,
                'vat_sum' => 6666,
            ],
            [
                'receipt_item' => $receipt_items->shift(),
                'company_service' => $company_service, //duplicate company service on receipt
                'creator_id' => auth()->id(),
                'price_net' => 1045,
                'price_net_sum' => 50000,
                'price_gross' => 1110,
                'price_gross_sum' => 33333,
                'vat_sum' => 3333,
            ],
            [
                'receipt_item' => $receipt_items->shift(),
                'company_service' => $company_services->shift(),
                'creator_id' => auth()->id(),
                'price_net' => 2010,
                'price_net_sum' => 10000,
                'price_gross' => 2460,
                'price_gross_sum' => 12300,
                'vat_sum' => 0,
            ],
            [
                'receipt_item' => $receipt_items->shift(),
                'company_service' => $company_services->shift(),
                'creator_id' => auth()->id(),
                'price_net' => 1000,
                'price_net_sum' => 10000,
                'price_gross' => 1230,
                'price_gross_sum' => 12300,
                'vat_sum' => 2300,
            ],
        ];
    }
}
