<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers\OnlineSaleController;

use App\Models\Db\ErrorLog;
use App\Models\Db\OnlineSaleItem;
use App\Models\Db\Package;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\OnlineSale;
use App\Models\Db\PaymentMethod;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\VatRate;
use App\Models\Db\CompanyService;
use Tests\BrowserKitTestCase;
use Tests\Helpers\CompanyTokenCreator;
use App\Helpers\ErrorCode;

class StoreTest extends BrowserKitTestCase
{
    use DatabaseTransactions, CompanyTokenCreator;

    /** @test */
    public function store_user_has_permission()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $this->post(
            'online-sales',
            [],
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->assertEquals(1, ErrorLog::count());
    }

    /** @test */
    public function store_it_returns_validation_error_without_data()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $this->post(
            'online-sales',
            [],
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'email',
            'number',
            'transaction_number',
            'sale_date',
            'price_net',
            'price_gross',
            'vat_sum',
            'items',
        ]);

        $this->assertEquals(1, ErrorLog::count());
    }

    /** @test */
    public function store_it_returns_validation_error_duplicate_transaction_number()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $transaction_number = '1234567890';
        $online_sale = factory(OnlineSale::class)->create();
        $online_sale->company_id = $company->id;
        $online_sale->transaction_number = $transaction_number;
        $online_sale->save();

        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($vat_rate);
        $incoming_data['transaction_number'] = $transaction_number;

        $this->post(
            '/online-sales',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        );

        $this->verifyErrorResponse(420, ErrorCode::OTHER_SALES_DUPLICATE_TRANSACTION_NUMBER);

        $this->assertEquals(1, ErrorLog::count());
    }

    /** @test */
    public function store_it_return_validation_error_empty_items()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();

        $transaction_number = '1234567890';
        $data_incoming = [
            'items' => [[]],
        ];
        $online_sale = factory(OnlineSale::class)->create();

        $this->post(
            'online-sales',
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
    }

    /** @test */
    public function store_it_return_validation_error_email()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $data_incoming = [
            'email' => 'no_valid_mail',
        ];
        $this->post(
            'online-sales',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'email',
        ]);

        $this->assertEquals(1, ErrorLog::count());
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
            'online-sales',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'items.0.vat_rate',
        ]);

        $this->assertEquals(1, ErrorLog::count());
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
            'online-sales',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'sale_date',
        ]);

        $this->assertEquals(1, ErrorLog::count());
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
            'online-sales',
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
    }

    /** @test */
    public function store_it_return_validation_error_amount_to_much()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $data_incoming = [
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
        ];
        $this->post(
            'online-sales',
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
    }

    /** @test */
    public function store_it_return_validation_error_amount_to_low()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $data_incoming = [
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
        ];
        $this->post(
            'online-sales',
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
            'online-sales',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'items.0.quantity',
        ]);

        $this->assertEquals(1, ErrorLog::count());

        $data_incoming = [
            'items' => [
                [
                    'quantity' => 99.99,
                ],
            ],
        ];
        $this->post(
            'online-sales',
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
            'email' => '1234567890@1234567890123456789012345678901234567890123456789012345678.pl',
            'transaction_number' => '1234567890123456789012345678901234567890123456789012345678901234567890',
            'number' => '1234567890123456789012345678901234567890123456789012345678901234567890',
            'items' => [
                [
                    'vat_rate' => '1234567890123456789012345678901234567890123456789012345678901234567890',
                ],
            ],
        ];
        $this->post(
            'online-sales',
            $data_incoming,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'email',
            'transaction_number',
            'number',
            'items.0.vat_rate',
        ]);

        $this->assertEquals(1, ErrorLog::count());
    }

    /** @test */
    public function store_validation_passing()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($vat_rate);

        $this->post(
            'online-sales',
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
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($vat_rate);

        CompanyService::whereRaw('1 = 1')->delete();
        $initials_company_service_amount = CompanyService::count();

        $vat_rate = new VatRate();

        $this->post(
            'online-sales',
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
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($vat_rate);

        CompanyService::whereRaw('1 = 1')->delete();

        $company_service = factory(CompanyService::class)->create();
        $company_service->name = 'service_1';
        $company_service->company_id = $company->id;
        $company_service->vat_rate_id = $vat_rate->id;
        $company_service->save();

        $initials_company_service_amount = CompanyService::count();

        $this->post(
            'online-sales',
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
    public function store_online_sale_was_add_to_database()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($vat_rate);
        $payment_method = factory(PaymentMethod::class)->create();

        OnlineSale::whereRaw('1 = 1')->delete();

        $initials_online_sale_amount = OnlineSale::count();

        $this->post(
            'online-sales',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201);

        $this->assertSame($initials_online_sale_amount + 1, OnlineSale::count());

        $online_sale = OnlineSale::latest('id')->first();

        $this->assertSame($incoming_data['number'], $online_sale->number);
        $this->assertSame(
            $incoming_data['transaction_number'],
            $online_sale->transaction_number
        );
        $this->assertSame($incoming_data['email'], $online_sale->email);
        $this->assertSame($company->id, $online_sale->company_id);
        $this->assertSame($incoming_data['sale_date'], $online_sale->sale_date);
        $this->assertSame(20054, $online_sale->price_net);
        $this->assertSame(24666, $online_sale->price_gross);
        $this->assertSame(4677, $online_sale->vat_sum);
        $this->assertSame(
            $payment_method::findBySlug(PaymentMethodType::BANK_TRANSFER)->id,
            $online_sale->payment_method_id
        );
        $this->assertNotNull($online_sale->created_at);
        $this->assertNotNull($online_sale->updated_at);

        $this->assertEquals(0, ErrorLog::count());
    }

    /** @test */
    public function store_online_sale_items_was_added_to_database()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($vat_rate);

        $initials_online_sale_items_amount = OnlineSaleItem::count();

        $this->post(
            'online-sales',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201);

        $this->assertSame(
            $initials_online_sale_items_amount + count($incoming_data['items']),
            OnlineSaleItem::count()
        );

        $online_sale = OnlineSale::latest('id')->first();
        $online_sale_items = OnlineSaleItem::latest('id')->limit(4)->get();
        $company_services = CompanyService::latest('id')->limit(3)->get();

        $expectationData =
            $this->expectationOnlineSaleItemsData($online_sale_items, $company_services);

        $item = count($incoming_data['items']);
        foreach ($expectationData as $expectation) {
            $this->assertSame(
                $incoming_data['items'][--$item]['name'],
                $expectation['online_sale_item']->name
            );
            $this->assertSame(
                $expectation['price_net'],
                $expectation['online_sale_item']->price_net
            );
            $this->assertSame(
                $expectation['price_net_sum'],
                $expectation['online_sale_item']->price_net_sum
            );
            $this->assertSame(
                $expectation['price_gross'],
                $expectation['online_sale_item']->price_gross
            );
            $this->assertSame(
                $expectation['price_gross_sum'],
                $expectation['online_sale_item']->price_gross_sum
            );
            $this->assertSame($expectation['vat_sum'], $expectation['online_sale_item']->vat_sum);
            $this->assertSame(
                $incoming_data['items'][$item]['vat_rate'],
                $expectation['online_sale_item']->vat_rate
            );
            $this->assertSame($vat_rate->id, $expectation['online_sale_item']->vat_rate_id);
            $this->assertSame(
                $incoming_data['items'][$item]['quantity'],
                $expectation['online_sale_item']->quantity
            );
            $this->assertSame($online_sale->id, $expectation['online_sale_item']->online_sale_id);
            $this->assertSame(
                $expectation['company_service']->id,
                $expectation['online_sale_item']->company_service_id
            );
            $this->assertSame(
                $expectation['company_service']->name,
                $expectation['online_sale_item']->name
            );
            $this->assertNotNull($expectation['online_sale_item']->created_at);
            $this->assertNotNull($expectation['online_sale_item']->updated_at);
        }

        $this->assertEquals(0, ErrorLog::count());
    }

    /** @test */
    public function store_company_services_was_used()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($vat_rate);

        CompanyService::whereRaw('1 = 1')->delete();

        $this->post(
            'online-sales',
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
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($vat_rate);

        $this->post(
            'online-sales',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201)
            ->seeJsonStructure([
                'data' => [
                    'id',
                    'email',
                    'number',
                    'transaction_number',
                    'company_id',
                    'sale_date',
                    'price_net',
                    'price_gross',
                    'vat_sum',
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
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($vat_rate);

        $this->post(
            'online-sales',
            $incoming_data,
            ['Authorization-Api-Token' => $api_token]
        )
            ->assertResponseStatus(201);

        $json = $this->decodeResponseJson()['data'];
        $online_sale = OnlineSale::latest('id')->first();
        $this->assertSame($online_sale->id, $json['id']);
        $this->assertSame($online_sale->number, $json['number']);
        $this->assertSame($online_sale->transaction_number, $json['transaction_number']);
        $this->assertSame($online_sale->email, $json['email']);
        $this->assertSame($online_sale->sale_date, $json['sale_date']);
        $this->assertSame(200.54, $json['price_net']);
        $this->assertSame(246.66, $json['price_gross']);
        $this->assertSame(46.77, $json['vat_sum']);
        $this->assertSame($online_sale->payment_method_id, $json['payment_method_id']);
        $this->assertSame($online_sale->created_at->toDateTimeString(), $json['created_at']);
        $this->assertSame($online_sale->updated_at->toDateTimeString(), $json['updated_at']);

        $this->assertEquals(0, ErrorLog::count());
    }

    protected function verify_error_log()
    {
        ErrorLog::all();
    }

    protected function login_user_and_return_company_with_his_employee_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        list($token, $api_token) = $this->createCompanyTokenForUser(
            $this->user,
            $company,
            RoleType::API_COMPANY
        );

        $now = Carbon::now();
        Carbon::setTestNow($now);

        return [$company, $api_token];
    }

    protected function store_init_incoming_data(VatRate $vat_rate)
    {
        return [
            'transaction_number' => '1234567890',
            'sale_date' => Carbon::now()->toDateTimeString(),
            'price_net' => 200.54,
            'price_gross' => 246.66,
            'vat_sum' => 46.77,
            'email' => 'admin@admin.pl',
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
                    'vat_sum' => 23.6,
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
                    'vat_sum' => 0,
                    'quantity' => 10,
                ],
            ],
        ];
    }

    protected function expectationOnlineSaleItemsData(Collection $online_sale_items, Collection $company_services)
    {
        return [
            [
                'online_sale_item' => $online_sale_items->shift(),
                'company_service' => ($company_service = $company_services->shift()),
                'price_net' => 3033,
                'price_net_sum' => 30020,
                'price_gross' => 3340,
                'price_gross_sum' => 33399,
                'vat_sum' => 0,
            ],
            [
                'online_sale_item' => $online_sale_items->shift(),
                'company_service' => $company_service, //duplicate company service on online_sale
                'price_net' => 1045,
                'price_net_sum' => 50000,
                'price_gross' => 1110,
                'price_gross_sum' => 33333,
                'vat_sum' => 3333,
            ],
            [
                'online_sale_item' => $online_sale_items->shift(),
                'company_service' => $company_services->shift(),
                'price_net' => 2010,
                'price_net_sum' => 10000,
                'price_gross' => 2460,
                'price_gross_sum' => 12300,
                'vat_sum' => 2360,
            ],
            [
                'online_sale_item' => $online_sale_items->shift(),
                'company_service' => $company_services->shift(),
                'price_net' => 1000,
                'price_net_sum' => 10000,
                'price_gross' => 1230,
                'price_gross_sum' => 12300,
                'vat_sum' => 2300,
            ],
        ];
    }
}
