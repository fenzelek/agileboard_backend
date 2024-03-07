<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers\ReceiptController;

use App\Models\Db\User;
use Carbon\Carbon;
use App\Models\Db\Receipt;
use App\Models\Db\Invoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Db\PaymentMethod;

class IndexTest extends ReceiptController
{
    use DatabaseTransactions;

    /** @test */
    public function index_user_has_permission()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->get('/receipts?selected_company_id=' . $company->id)
            ->assertResponseOk();
    }

    /** @test */
    public function index_response_structure_without_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);
        $this->get('/receipts?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'number',
                        'transaction_number',
                        'user_id',
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

        $receipt = factory(Receipt::class)->create();
        $receipt->company_id = $company->id;
        $receipt->save();
        $invoice = factory(Invoice::class)->create();
        $receipt->invoices()->attach($invoice->id);
        $this->get('/receipts?selected_company_id=' . $company->id, [])
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
    public function index_retrieve_amount_of_receipts_equals_to_amount_add_to_database()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 3)->create();
        $this->assignReceiptsToCompany($receipts, $company);
        $amount_receipts = Receipt::count();

        $this->get('/receipts?selected_company_id=' . $company->id, [])
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($amount_receipts, count($json_data));
    }

    /** @test */
    public function index_response_has_correct_data()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipt = factory(Receipt::class)->create();
        $receipt->price_net = 10022;
        $receipt->price_gross = 20022;
        $receipt->vat_sum = 2322;
        $receipt->company_id = $company->id;
        $receipt->save();
        $invoice = factory(Invoice::class)->create();
        $receipt->invoices()->attach($invoice->id);

        $this->get('/receipts?selected_company_id=' . $company->id)
            ->assertResponseOk();
        $json_data = $this->decodeResponseJson()['data'];
        $receipt = $receipt->fresh();
        $this->assertSame($receipt->id, $json_data[0]['id']);
        $this->assertSame($receipt->number, $json_data[0]['number']);
        $this->assertSame($receipt->transaction_number, $json_data[0]['transaction_number']);
        $this->assertSame($receipt->user_id, $json_data[0]['user_id']);
        $this->assertSame($receipt->company_id, $json_data[0]['company_id']);
        $this->assertSame($receipt->sale_date, $json_data[0]['sale_date']);
        $this->assertSame(100.22, $json_data[0]['price_net']);
        $this->assertSame(200.22, $json_data[0]['price_gross']);
        $this->assertSame(23.22, $json_data[0]['vat_sum']);
        $this->assertSame($receipt->payment_method_id, $json_data[0]['payment_method_id']);
        $this->assertEquals($receipt->created_at, $json_data[0]['created_at']);
        $this->assertEquals($receipt->updated_at, $json_data[0]['updated_at']);

        $invoice_data = $json_data[0]['invoices']['data'];
        $this->assertSame(1, count($invoice_data));
        $this->assertSame($invoice->id, $invoice_data[0]['id']);
        $this->assertEquals($invoice->number, $invoice_data[0]['number']);
    }

    /** @test */
    public function index_data_was_only_for_indicate_company()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipt_other_company = factory(Receipt::class)->create();
        $receipts_amount = Receipt::count();

        $this->get('/receipts?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($receipts_amount - 1, count($json_data));
        $first_receipt_from_response = array_pop($json_data);
        $this->assertSame($company->id, $first_receipt_from_response['company_id']);
        $second_receipt_from_response = array_pop($json_data);
        $this->assertSame($company->id, $second_receipt_from_response['company_id']);
    }

    /** @test */
    public function index_data_was_filter_by_user()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $other_user = factory(User::class)->create();
        $receipts[0]->user_id = $other_user->id;
        $receipts[0]->save();

        $filter_user = $other_user->id;

        $this->get('receipts?selected_company_id=' . $company->id . '&user_id=' . $filter_user)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1, count($json_data));
        $first_receipt_response = array_pop($json_data);
        $this->assertSame($filter_user, $first_receipt_response['user_id']);
    }

    /** @test */
    public function index_validate_error_user_id_not_integer()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('receipts?selected_company_id=' . $company->id . '&user_id=' . 'not_number')
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'user_id',
        ]);
    }

    /** @test */
    public function index_validate_error_no_invoice_not_boolean()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('receipts?selected_company_id=' . $company->id . '&no_invoice=' . 'not_valid_boolean')
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'no_invoice',
        ]);
    }

    /** @test */
    public function index_validate_error_filter_date_not_date_format()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $filter_date_start = 'not_date';
        $filter_date_end = '2017-01-01';

        $this->get(
            '/receipts?selected_company_id=' . $company->id
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
    public function index_retrieve_sale_filter_by_year_and_data_start()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 3)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();

        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();

        $receipts[2]->sale_date = Carbon::parse('2017-02-11');
        $receipts[2]->save();

        $this->get('receipts?selected_company_id=' . $company->id
            . '&year=' . 2016
            . '&date_start=' . '2016-02-01')
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1, count($json_data));
        $this->assertSame($receipts[1]->id, $json_data[0]['id']);
    }

    /** @test */
    public function index_validation_error_invalid_month_and_year()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('receipts?selected_company_id=' . $company->id
            . '&month=' . 'no_integer' . '&year=' . 'no_integer')
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('receipts?selected_company_id=' . $company->id
            . '&month=' . 13 . '&year=' . 2051)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('receipts?selected_company_id=' . $company->id
            . '&month=' . 0 . '&year=' . 2000)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('receipts?selected_company_id=' . $company->id
            . '&month=' . 12)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse(['year']);
    }

    /** @test */
    public function index_retrieve_receipts_only_without_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 4)->create();
        $this->assignReceiptsToCompany($receipts, $company);
        $receipts[0]->price_net = 10022;
        $receipts[0]->price_gross = 20022;
        $receipts[0]->vat_sum = 2322;
        $receipts[0]->save();
        $receipts[3]->price_net = 110022;
        $receipts[3]->price_gross = 120022;
        $receipts[3]->vat_sum = 12322;
        $receipts[3]->save();
        $invoice = factory(Invoice::class)->create();
        $receipts[1]->invoices()->attach($invoice->id);
        $receipts[2]->invoices()->attach($invoice->id);

        $this->get('receipts?selected_company_id=' . $company->id . '&no_invoice=1')
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $receipts[0] = $receipts[0]->fresh();
        $receipts[3] = $receipts[3]->fresh();
        $this->assertSame(2, count($json_data));
        $i = 0;
        foreach ([$receipts[0], $receipts[3]] as $receipt) {
            $this->assertSame($receipt->id, $json_data[$i]['id']);
            $this->assertSame($receipt->number, $json_data[$i]['number']);
            $this->assertSame($receipt->transaction_number, $json_data[$i]['transaction_number']);
            $this->assertSame($receipt->user_id, $json_data[$i]['user_id']);
            $this->assertSame($receipt->company_id, $json_data[$i]['company_id']);
            $this->assertSame($receipt->sale_date, $json_data[$i]['sale_date']);
            $this->assertSame($receipt->payment_method_id, $json_data[$i]['payment_method_id']);
            $this->assertEquals($receipt->created_at, $json_data[$i]['created_at']);
            $this->assertEquals($receipt->updated_at, $json_data[$i]['updated_at']);
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
    public function index_validate_error_filter_payment_method_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $payment_method_id = factory(PaymentMethod::class)->create();
        $receipts[0]->payment_method_id = $payment_method_id->id;
        $receipts[0]->save();

        $this->get(
            'receipts?selected_company_id=' . $company->id
            . '&payment_method_id=' . $payment_method_id->id
        )->assertResponseOk();
    }

    /** @test */
    public function index_data_was_filter_by_date_long_period()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();

        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $expect =
            [
                'date_start' => Carbon::parse('2016-01-01'),
                'date_end' => Carbon::parse('2016-05-01'),
                'expect_amount' => 2,
                'receipts' => $receipts,
            ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_boundary_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();

        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $expect =
            [
                'date_start' => Carbon::parse('2016-01-10 00:00:00'),
                'date_end' => Carbon::parse('2016-02-11 23:59:59'),
                'expect_amount' => 2,
                'receipts' => $receipts,
            ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_date_in_time_short_period_after_limit()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();

        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();

        $expect = [
            'date_start' => Carbon::parse('2016-01-01'),
            'date_end' => Carbon::parse('2016-01-31'),
            'expect_amount' => 1,
            'receipts' => [$receipts[0]],
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_date_in_time_short_period_before_limit()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();

        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();

        $expect = [
            'date_start' => Carbon::parse('2016-02-01'),
            'date_end' => Carbon::parse('2016-03-31'),
            'expect_amount' => 1,
            'receipts' => [$receipts[1]],
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_cause_only_start_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();

        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();

        $expect = [
            'date_start' => Carbon::parse('2016-02-01'),
            'expect_amount' => 1,
            'receipts' => [$receipts[1]],
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_cause_like_start_date_after_end_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();

        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();

        $expect = [
            'date_start' => Carbon::parse('2016-02-20'),
            'date_end' => Carbon::parse('2016-02-12'),
            'expect_amount' => 0,
            'receipts' => [],
        ];

        $date_start = isset($expect['date_start']) ? $expect['date_start'] : '';
        $date_end = isset($expect['date_end']) ? $expect['date_end'] : '';
        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_only_end_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);
        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();

        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();

        $expect = [
            'date_end' => Carbon::parse('2016-03-31'),
            'expect_amount' => 2,
            'receipts' => $receipts,
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_end_date_before_start_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();

        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();

        $expect = [
            'date_start' => Carbon::parse('2016-01-11'),
            'date_end' => Carbon::parse('2016-01-01'),
            'expect_amount' => 0,
            'receipts' => [],
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $expect['date_start']
            . '&date_end=' . $expect['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_transaction_number_lower_letters()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);
        $receipts[0]->transaction_number = 'ABCDEFGH';
        $receipts[0]->number = 'xxx';
        $receipts[0]->save();

        $receipts[1]->number = '1234567890';
        $receipts[1]->transaction_number = 'xxx';
        $receipts[1]->save();

        $expect = [
            'transaction_number' => 'abc',
            'expect_amount' => 1,
            'receipts' => [$receipts[0]],
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&transaction_number=' . $expect['transaction_number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_transaction_number_upper_letters()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->transaction_number = 'ABCDEFGH';
        $receipts[0]->save();

        $receipts[1]->number = '1234567890';
        $receipts[1]->save();

        $expect = [
            'transaction_number' => 'EfGh',
            'expect_amount' => 1,
            'receipts' => [$receipts[0]],
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&transaction_number=' . $expect['transaction_number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_number_as_numeric()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->transaction_number = 'ABCDEFGH';
        $receipts[0]->save();

        $receipts[1]->number = '1234567890';
        $receipts[1]->save();

        $expect = [
            'number' => 123,
            'expect_amount' => 1,
            'receipts' => [$receipts[1]],
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&number=' . $expect['number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_number_as_string()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->transaction_number = 'ABCDEFGH';
        $receipts[0]->number = '0987654321';
        $receipts[0]->save();

        $receipts[0]->transaction_number = 'HGFEDCBA';
        $receipts[1]->number = '1234567890';
        $receipts[1]->save();

        $expect = [
            'number' => '123',
            'expect_amount' => 1,
            'receipts' => [$receipts[1]],
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&number=' . $expect['number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_number_and_transaction_number_both_in_same_time_unknown()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);

        $receipts[0]->transaction_number = 'ABCDEFGH';
        $receipts[0]->save();

        $receipts[1]->number = '1234567890';
        $receipts[1]->save();

        $expect = [
            'transaction_number' => 'unknown_transaction_number',
            'number' => 'unknown_number',
            'expect_amount' => 0,
            'receipts' => [],
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&transaction_number=' . $expect['transaction_number']
            . '&number=' . $expect['number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }

    /** @test */
    public function index_data_was_filter_by_number_and_transaction_number_both_in_same_time_to_many_filter()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        factory(Receipt::class)->create([
            'transaction_number' => 'ABC',
            'number' => '456',
        ]);
        factory(Receipt::class)->create([
            'transaction_number' => 'DEF',
            'number' => '123',
        ]);
        $this->assignReceiptsToCompany(Receipt::all(), $company);

        $expect = [
            'transaction_number' => 'ABC',
            'number' => '123',
            'expect_amount' => 0,
            'receipts' => [],
        ];

        $this->get(
            '/receipts?selected_company_id=' . $company->id
            . '&transaction_number=' . $expect['transaction_number']
            . '&number=' . $expect['number']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($expect['expect_amount'], count($json_data));
        foreach ($expect['receipts'] as $key => $receipt) {
            $this->assertSame($receipt->id, $json_data[$key]['id']);
        }
    }
}
