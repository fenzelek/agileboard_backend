<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers\OnlineSaleController;

use Carbon\Carbon;
use App\Models\Db\OnlineSale;
use App\Models\Db\Invoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Db\Company;
use Tests\BrowserKitTestCase;

class IndexTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_user_has_permission()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->get('online-sales/?selected_company_id=' . $company->id)
            ->assertResponseOk();
    }

    /** @test */
    public function index_response_structure_without_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);
        $this->get('online-sales?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'number',
                        'transaction_number',
                        'email',
                        'company_id',
                        'sale_date',
                        'price_net',
                        'price_gross',
                        'vat_sum',
                        'payment_method_id',
                        'created_at',
                        'updated_at',
                    ],

                ],
            ]);
    }

    /** @test */
    public function index_response_structure_with_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $online_sale = factory(OnlineSale::class)->create();
        $online_sale->company_id = $company->id;
        $online_sale->save();
        $invoice = factory(Invoice::class)->create();
        $online_sale->invoices()->attach($invoice->id);
        $this->get('online-sales?selected_company_id=' . $company->id, [])
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [
                    [
                        'invoices' => [
                            'data' => [
                                [
                                    'id',
                                    'number',
                                ],

                            ],
                        ],
                    ],

                ],
            ]);
    }

    /** @test */
    public function index_retrieve_amount_of_online_sales_equals_to_amount_add_to_database()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 3)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);
        $amount_online_sales = OnlineSale::count();

        $this->get('online-sales?selected_company_id=' . $company->id, [])
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($amount_online_sales, count($json_data));
    }

    /** @test */
    public function index_response_has_correct_data()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sale = factory(OnlineSale::class)->create();
        $online_sale->price_net = 10022;
        $online_sale->price_gross = 20022;
        $online_sale->vat_sum = 2322;
        $online_sale->company_id = $company->id;
        $online_sale->save();
        $invoice = factory(Invoice::class)->create();
        $online_sale->invoices()->attach($invoice->id);

        $this->get('online-sales?selected_company_id=' . $company->id)
            ->assertResponseOk();
        $json_data = $this->decodeResponseJson()['data'];
        $online_sale = $online_sale->fresh();
        $this->assertSame($online_sale->id, $json_data[0]['id']);
        $this->assertSame($online_sale->number, $json_data[0]['number']);
        $this->assertSame($online_sale->transaction_number, $json_data[0]['transaction_number']);
        $this->assertSame($online_sale->email, $json_data[0]['email']);
        $this->assertSame($online_sale->company_id, $json_data[0]['company_id']);
        $this->assertSame($online_sale->sale_date, $json_data[0]['sale_date']);
        $this->assertSame(100.22, $json_data[0]['price_net']);
        $this->assertSame(200.22, $json_data[0]['price_gross']);
        $this->assertSame(23.22, $json_data[0]['vat_sum']);
        $this->assertSame($online_sale->payment_method_id, $json_data[0]['payment_method_id']);
        $this->assertEquals($online_sale->created_at, $json_data[0]['created_at']);
        $this->assertEquals($online_sale->updated_at, $json_data[0]['updated_at']);

        $invoice_data = $json_data[0]['invoices']['data'];
        $this->assertSame(1, count($invoice_data));
        $this->assertSame($invoice->id, $invoice_data[0]['id']);
        $this->assertEquals($invoice->number, $invoice_data[0]['number']);
    }

    /** @test */
    public function index_data_was_only_for_indicate_company()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sale_other_company = factory(OnlineSale::class)->create();
        $online_sales_amount = OnlineSale::count();

        $this->get('online-sales?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($online_sales_amount - 1, count($json_data));
        $first_online_sale_from_response = array_pop($json_data);
        $this->assertSame($company->id, $first_online_sale_from_response['company_id']);
        $second_online_sale_from_response = array_pop($json_data);
        $this->assertSame($company->id, $second_online_sale_from_response['company_id']);
    }

    /** @test */
    public function index_data_was_filter_by_user()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $filter_email = 'admin@admin.pl';

        $online_sales[0]->email = $filter_email;
        $online_sales[0]->save();

        $this->get('online-sales?selected_company_id=' . $company->id . '&email=' . $filter_email)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1, count($json_data));
        $first_online_sale_response = array_pop($json_data);
        $this->assertSame($filter_email, $first_online_sale_response['email']);
    }

    /** @test */
    public function index_validate_error_user_id_not_integer()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('online-sales?selected_company_id=' . $company->id . '&email=' . 'not_valid_email')
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'email',
        ]);
    }

    /** @test */
    public function index_validate_error_filter_date_not_date_format()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $filter_date_start = 'not_date';
        $filter_date_end = '2017-01-01';

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $filter_date_start
            . '&date_end=' . $filter_date_end
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse(
            [
                'date_start',
            ],
            [
                'date_end',
            ]
        );
    }

    /** @test */
    public function index_validate_error_no_invoice_not_boolean()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('online-sales?selected_company_id=' . $company->id . '&no_invoice=' . 'not_valid_boolean')
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'no_invoice',
        ]);
    }

    /** @test */
    public function index_data_was_filter_by_date_long_period()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $expect =
        [
            'date_start' => Carbon::parse('2016-01-01'),
            'date_end' => Carbon::parse('2016-05-01'),
            'expect_amount' => 2,
            'online_sales' => $online_sales,
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_boundary_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $expect =
        [
            'date_start' => Carbon::parse('2016-01-10 00:00:00'),
            'date_end' => Carbon::parse('2016-02-11 23:59:59'),
            'expect_amount' => 2,
            'online_sales' => $online_sales,
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_date_in_time_short_period_after_limit()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();

        $expect = [
            'date_start' => Carbon::parse('2016-01-01'),
            'date_end' => Carbon::parse('2016-01-31'),
            'expect_amount' => 1,
            'online_sales' => [$online_sales[0]],
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_date_in_time_short_period_before_limit()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();

        $expect = [
            'date_start' => Carbon::parse('2016-02-01'),
            'date_end' => Carbon::parse('2016-03-31'),
            'expect_amount' => 1,
            'online_sales' => [$online_sales[1]],
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_cause_only_start_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();

        $expect = [
            'date_start' => Carbon::parse('2016-02-01'),
            'expect_amount' => 1,
            'online_sales' => [$online_sales[1]],
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_cause_like_start_date_after_end_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();

        $expect = [
            'date_start' => Carbon::parse('2016-02-20'),
            'date_end' => Carbon::parse('2016-02-12'),
            'expect_amount' => 0,
            'online_sales' => [],
        ];

        $date_start = isset($expect['date_start']) ? $expect['date_start'] : '';
        $date_end = isset($expect['date_end']) ? $expect['date_end'] : '';
        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_only_end_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();

        $expect = [
            'date_end' => Carbon::parse('2016-03-31'),
            'expect_amount' => 2,
            'online_sales' => $online_sales,
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_end_date_before_start_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();

        $expect = [
            'date_start' => Carbon::parse('2016-01-11'),
            'date_end' => Carbon::parse('2016-01-01'),
            'expect_amount' => 0,
            'online_sales' => [],
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_transaction_number_lower_letters()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->transaction_number = 'ABCDEFGH';
        $online_sales[0]->number = '0987456321';
        $online_sales[0]->save();

        $online_sales[1]->transaction_number = 'EFRGTHYJ';
        $online_sales[1]->number = '1234567890';
        $online_sales[1]->save();

        $expect = [
            'transaction_number' => 'abc',
            'expect_amount' => 1,
            'online_sales' => [$online_sales[0]],
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&transaction_number=' . $expect['transaction_number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_transaction_number_upper_letters()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->transaction_number = 'ABCDEFGH';
        $online_sales[0]->number = '0987456321';
        $online_sales[0]->save();

        $online_sales[1]->transaction_number = 'EFRGTHYJ';
        $online_sales[1]->number = '1234567890';
        $online_sales[1]->save();

        $expect = [
            'transaction_number' => 'EfGh',
            'expect_amount' => 1,
            'online_sales' => [$online_sales[0]],
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&transaction_number=' . $expect['transaction_number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_number_as_numeric()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->transaction_number = 'ABCDEFGH';
        $online_sales[0]->number = '0987456321';
        $online_sales[0]->save();

        $online_sales[1]->transaction_number = 'EFRGTHYJ';
        $online_sales[1]->number = '1234567890';
        $online_sales[1]->save();

        $expect = [
            'number' => 123,
            'expect_amount' => 1,
            'online_sales' => [$online_sales[1]],
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&number=' . $expect['number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_number_as_string()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->transaction_number = 'ABCDEFGH';
        $online_sales[0]->number = '0987456321';
        $online_sales[0]->save();

        $online_sales[1]->transaction_number = 'EFRGTHYJ';
        $online_sales[1]->number = '1234567890';
        $online_sales[1]->save();

        $expect = [
            'number' => '123',
            'expect_amount' => 1,
            'online_sales' => [$online_sales[1]],
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&number=' . $expect['number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_number_and_transaction_number_both_in_same_time_unknown()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->transaction_number = 'ABCDEFGH';
        $online_sales[0]->number = '0987456321';
        $online_sales[0]->save();

        $online_sales[1]->transaction_number = 'EFRGTHYJ';
        $online_sales[1]->number = '1234567890';
        $online_sales[1]->save();

        $expect = [
            'transaction_number' => 'unknown_transaction_number',
            'number' => 'unknown_number',
            'expect_amount' => 0,
            'online_sales' => [],
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&transaction_number=' . $expect['transaction_number']
            . '&number=' . $expect['number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_number_and_transaction_number_both_in_same_time_to_many_filter()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        factory(OnlineSale::class)->create([
            'transaction_number' => 'ABC',
            'number' => '456',
        ]);
        factory(OnlineSale::class)->create([
            'transaction_number' => 'DEF',
            'number' => '123',
        ]);
        $this->assignOnlineSalesToCompany(OnlineSale::all(), $company);

        $expect = [
            'transaction_number' => 'ABC',
            'number' => '123',
            'expect_amount' => 0,
            'online_sales' => [],
        ];

        $this->get(
            'online-sales?selected_company_id=' . $company->id
            . '&transaction_number=' . $expect['transaction_number']
            . '&number=' . $expect['number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['online_sales'] as $key => $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_retrieve_sale_only_without_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 4)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);
        $online_sales[0]->price_net = 10022;
        $online_sales[0]->price_gross = 20022;
        $online_sales[0]->vat_sum = 2322;
        $online_sales[0]->save();
        $online_sales[3]->price_net = 110022;
        $online_sales[3]->price_gross = 120022;
        $online_sales[3]->vat_sum = 12322;
        $online_sales[3]->save();
        $invoice = factory(Invoice::class)->create();
        $online_sales[1]->invoices()->attach($invoice->id);
        $online_sales[2]->invoices()->attach($invoice->id);

        $this->get('online-sales?selected_company_id=' . $company->id . '&no_invoice=1')
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $online_sales[0] = $online_sales[0]->fresh();
        $online_sales[3] = $online_sales[3]->fresh();
        $this->assertSame(2, count($json_data));
        $i = 0;
        foreach ([$online_sales[0], $online_sales[3]] as $online_sale) {
            $this->assertSame($online_sale->id, $json_data[$i]['id']);
            $this->assertSame($online_sale->number, $json_data[$i]['number']);
            $this->assertSame($online_sale->transaction_number, $json_data[$i]['transaction_number']);
            $this->assertSame($online_sale->email, $json_data[$i]['email']);
            $this->assertSame($online_sale->company_id, $json_data[$i]['company_id']);
            $this->assertSame($online_sale->sale_date, $json_data[$i]['sale_date']);
            $this->assertSame($online_sale->payment_method_id, $json_data[$i]['payment_method_id']);
            $this->assertEquals($online_sale->created_at, $json_data[$i]['created_at']);
            $this->assertEquals($online_sale->updated_at, $json_data[$i]['updated_at']);
            $invoice_data = $json_data[$i]['invoices']['data'];
            $this->assertSame(0, count($invoice_data));
            ++$i;
        }
        $this->assertSame(100.22, $json_data[0]['price_net']);
        $this->assertSame(200.22, $json_data[0]['price_gross']);
        $this->assertSame(23.22, $json_data[0]['vat_sum']);
        $this->assertSame(1100.22, $json_data[1]['price_net']);
        $this->assertSame(1200.22, $json_data[1]['price_gross']);
        $this->assertSame(123.22, $json_data[1]['vat_sum']);
    }

    /** @test */
    public function index_retrieve_sale_filter_by_year_and_month()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();

        $this->get('online-sales?selected_company_id=' . $company->id
            . '&year=' . 2016 . '&month=' . 1)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1, count($json_data));
        $this->assertSame($online_sales[0]->id, $json_data[0]['id']);
    }

    /** @test */
    public function index_retrieve_sale_filter_by_year()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 3)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();

        $online_sales[2]->sale_date = Carbon::parse('2017-02-11');
        $online_sales[2]->save();

        $this->get('online-sales?selected_company_id=' . $company->id
            . '&year=' . 2016)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(2, count($json_data));
        $this->assertSame($online_sales[0]->id, $json_data[0]['id']);
        $this->assertSame($online_sales[1]->id, $json_data[1]['id']);
    }

    /** @test */
    public function index_retrieve_sale_filter_by_year_and_data_start()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 3)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();

        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();

        $online_sales[2]->sale_date = Carbon::parse('2017-02-11');
        $online_sales[2]->save();

        $this->get('online-sales?selected_company_id=' . $company->id
            . '&year=' . 2016
            . '&date_start=' . '2016-02-01')
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1, count($json_data));
        $this->assertSame($online_sales[1]->id, $json_data[0]['id']);
    }

    /** @test */
    public function index_validation_error_invalid_month_and_year()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('online-sales?selected_company_id=' . $company->id
            . '&month=' . 'no_integer' . '&year=' . 'no_integer')
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('online-sales?selected_company_id=' . $company->id
            . '&month=' . 13 . '&year=' . 2051)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('online-sales?selected_company_id=' . $company->id
            . '&month=' . 0 . '&year=' . 2000)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('online-sales?selected_company_id=' . $company->id
            . '&month=' . 12)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse(['year']);
    }

    protected function login_user_and_return_company_with_his_employee_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $company = $this->createCompanyWithRole(RoleType::OWNER);

        return $company;
    }

    protected function assignOnlineSalesToCompany(Collection $online_sales, Company $company)
    {
        $online_sales->each(function ($online_sale) use ($company) {
            $online_sale->company_id = $company->id;
            $online_sale->save();
        });
    }
}
