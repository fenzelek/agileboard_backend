<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers;

use App\Models\Db\Contractor;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\InvoiceType;
use App\Models\Db\Package;
use App\Models\Other\InvoiceMarginProcedureType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\SaleInvoice\FilterOption;
use Carbon\Carbon;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor as ModelInvoiceContractor;
use App\Models\Db\Contractor as ModelContractor;
use App\Models\Db\Receipt as ModelReceipt;
use App\Models\Db\OnlineSale as ModelOnlineSale;
use App\Models\Db\InvoiceInvoice as ModelInvoiceInvoice;
use App\Models\Db\InvoiceReceipt as ModelInvoiceReceipt;
use App\Models\Db\InvoiceOnlineSale as ModelInvoiceOnlineSale;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use File;
use Tests\BrowserKitTestCase;
use Tests\Helpers\StringHelper;

class InvoiceControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, StringHelper;

    /** @test */
    public function test_index_data_structure()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        $contractor = factory(ModelContractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'drawer_id' => $this->user->id,
            'price_net' => 5425,
            'price_gross' => 6418,
            'vat_sum' => 754,
            'payment_left' => 4854,
        ]);
        factory(ModelInvoiceContractor::class, 3)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
        ]);
        $receipt = factory(ModelReceipt::class)->create([
            'company_id' => $company->id,
            'price_net' => 4521,
            'price_gross' => 5487,
            'vat_sum' => 451,
        ]);
        $online_sale = factory(ModelOnlineSale::class)->create([
            'company_id' => $company->id,
            'price_net' => 3245,
            'price_gross' => 4215,
            'vat_sum' => 584,
        ]);

        factory(ModelInvoiceReceipt::class)->create([
            'invoice_id' => $invoice->id,
            'receipt_id' => $receipt->id,
        ]);
        factory(ModelInvoiceOnlineSale::class)->create([
            'invoice_id' => $invoice->id,
            'online_sale_id' => $online_sale->id,
        ]);
        factory(ModelInvoiceInvoice::class)->create([
            'parent_id' => $invoice->id,
            'node_id' => $invoice->id,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'number',
                        'order_number',
                        'invoice_registry_id',
                        'drawer_id',
                        'company_id',
                        'contractor_id',
                        'sale_date',
                        'issue_date',
                        'invoice_type_id',
                        'invoice_margin_procedure_id',
                        'price_net',
                        'price_gross',
                        'vat_sum',
                        'payment_left',
                        'payment_term_days',
                        'payment_method_id',
                        'paid_at',
                        'gross_counted',
                        'last_printed_at',
                        'last_send_at',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'is_editable',
                        'drawer' => [
                            'data' => [
                                'id',
                                'first_name',
                                'last_name',
                            ],
                        ],
                        'invoice_contractor' => [
                            'data' => [
                                'id',
                                'invoice_id',
                                'contractor_id',
                                'name',
                                'vatin',
                                'email',
                                'phone',
                                'bank_name',
                                'bank_account_number',
                                'main_address_street',
                                'main_address_number',
                                'main_address_zip_code',
                                'main_address_city',
                                'main_address_country',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                        'receipts' => [
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
                        ],
                        'online_sales' => [
                            'data' => [
                                [
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
                            ],
                        ],
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
                'meta' => [
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                        'links',
                    ],
                ],
            ])->isJson();
    }

    /** @test */
    public function test_index_with_invalid_company_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
        ]);

        $this->get('/invoices?selected_company_id=' . ($company->id + 8))
            ->seeStatusCode(401);
    }

    /** @test */
    public function test_index_with_correct_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 5425,
            'price_gross' => 6418,
            'vat_sum' => 754,
            'payment_left' => 4854,
            'invoice_margin_procedure_id' => InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id,
        ]);
        $receipt = factory(ModelReceipt::class)->create([
            'company_id' => $company->id,
            'price_net' => 4521,
            'price_gross' => 5487,
            'vat_sum' => 451,
        ]);
        $online_sale = factory(ModelOnlineSale::class)->create([
            'company_id' => $company->id,
            'price_net' => 3245,
            'price_gross' => 4215,
            'vat_sum' => 584,
        ]);

        factory(ModelInvoiceReceipt::class)->create([
            'invoice_id' => $invoice->id,
            'receipt_id' => $receipt->id,
        ]);
        factory(ModelInvoiceOnlineSale::class)->create([
            'invoice_id' => $invoice->id,
            'online_sale_id' => $online_sale->id,
        ]);

        // Created soft deleted invoice.
        $now = Carbon::parse('2017-05-11 08:00:00');
        Carbon::setTestNow($now);

        $deleted_invoice = factory(Invoice::class)->create([
            'price_net' => 10000,
            'price_gross' => 12300,
            'vat_sum' => 2300,
            'payment_left' => 10000,
            'company_id' => $company->id,
            'deleted_at' => $now->toDateTimeString(),
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id);

        $response = $this->decodeResponseJson();
        $data = $response['data'];

        $this->assertEquals(2, count($data));

        // First invoice - data
        $this->assertEquals($invoice->id, $data[0]['id']);
        $this->assertEquals($invoice->number, $data[0]['number']);
        $this->assertEquals($invoice->order_number, $data[0]['order_number']);
        $this->assertEquals($invoice->invoice_registry_id, $data[0]['invoice_registry_id']);
        $this->assertEquals($invoice->drawer_id, $data[0]['drawer_id']);
        $this->assertEquals($invoice->company_id, $data[0]['company_id']);
        $this->assertEquals($invoice->contractor_id, $data[0]['contractor_id']);
        $this->assertEquals($invoice->sale_date, $data[0]['sale_date']);
        $this->assertEquals($invoice->issue_date, $data[0]['issue_date']);
        $this->assertEquals($invoice->invoice_type_id, $data[0]['invoice_type_id']);
        $this->assertEquals($invoice->invoice_margin_procedure_id, $data[0]['invoice_margin_procedure_id']);
        $this->assertEquals(54.25, $data[0]['price_net']);
        $this->assertEquals(64.18, $data[0]['price_gross']);
        $this->assertEquals(7.54, $data[0]['vat_sum']);
        $this->assertEquals(48.54, $data[0]['payment_left']);
        $this->assertEquals($invoice->payment_term_days, $data[0]['payment_term_days']);
        $this->assertEquals($invoice->payment_method_id, $data[0]['payment_method_id']);
        $this->assertEquals($invoice->paid_at, $data[0]['paid_at']);
        $this->assertEquals($invoice->gross_counted, $data[0]['gross_counted']);
        $this->assertEquals($invoice->last_printed_at, $data[0]['last_printed_at']);
        $this->assertEquals($invoice->last_send_at, $data[0]['last_send_at']);
        $this->assertEquals($invoice->created_at, $data[0]['created_at']);
        $this->assertEquals($invoice->updated_at, $data[0]['updated_at']);
        $this->assertEquals($invoice->deleted_at, $data[0]['deleted_at']);
        $this->assertTrue($data[0]['is_editable']);

        // First invoice - receipts
        $this->assertEquals($receipt->id, $data[0]['receipts']['data'][0]['id']);
        $this->assertEquals($receipt->number, $data[0]['receipts']['data'][0]['number']);
        $this->assertEquals(
            $receipt->transaction_number,
            $data[0]['receipts']['data'][0]['transaction_number']
        );
        $this->assertEquals($receipt->user_id, $data[0]['receipts']['data'][0]['user_id']);
        $this->assertEquals($receipt->company_id, $data[0]['receipts']['data'][0]['company_id']);
        $this->assertEquals(45.21, $data[0]['receipts']['data'][0]['price_net']);
        $this->assertEquals(54.87, $data[0]['receipts']['data'][0]['price_gross']);
        $this->assertEquals(4.51, $data[0]['receipts']['data'][0]['vat_sum']);
        $this->assertEquals(
            $receipt->payment_method_id,
            $data[0]['receipts']['data'][0]['payment_method_id']
        );

        // First invoice - online sales
        $this->assertEquals($online_sale->id, $data[0]['online_sales']['data'][0]['id']);
        $this->assertEquals($online_sale->email, $data[0]['online_sales']['data'][0]['email']);
        $this->assertEquals($online_sale->number, $data[0]['online_sales']['data'][0]['number']);
        $this->assertEquals(
            $online_sale->transaction_number,
            $data[0]['online_sales']['data'][0]['transaction_number']
        );
        $this->assertEquals(
            $online_sale->company_id,
            $data[0]['online_sales']['data'][0]['company_id']
        );
        $this->assertEquals(32.45, $data[0]['online_sales']['data'][0]['price_net']);
        $this->assertEquals(42.15, $data[0]['online_sales']['data'][0]['price_gross']);
        $this->assertEquals(5.84, $data[0]['online_sales']['data'][0]['vat_sum']);
        $this->assertEquals(
            $online_sale->payment_method_id,
            $data[0]['online_sales']['data'][0]['payment_method_id']
        );

        // Second invoice (soft deleted) - data
        $this->assertEquals($deleted_invoice->id, $data[1]['id']);
        $this->assertEquals($deleted_invoice->number, $data[1]['number']);
        $this->assertEquals($deleted_invoice->order_number, $data[1]['order_number']);
        $this->assertEquals($deleted_invoice->invoice_registry_id, $data[1]['invoice_registry_id']);
        $this->assertEquals($deleted_invoice->drawer_id, $data[1]['drawer_id']);
        $this->assertEquals($deleted_invoice->company_id, $data[1]['company_id']);
        $this->assertEquals($deleted_invoice->contractor_id, $data[1]['contractor_id']);
        $this->assertEquals($deleted_invoice->sale_date, $data[1]['sale_date']);
        $this->assertEquals($deleted_invoice->issue_date, $data[1]['issue_date']);
        $this->assertEquals($deleted_invoice->invoice_type_id, $data[1]['invoice_type_id']);
        $this->assertEquals(100.00, $data[1]['price_net']);
        $this->assertEquals(123.00, $data[1]['price_gross']);
        $this->assertEquals(23, $data[1]['vat_sum']);
        $this->assertEquals(100, $data[1]['payment_left']);
        $this->assertEquals($deleted_invoice->payment_term_days, $data[1]['payment_term_days']);
        $this->assertEquals($deleted_invoice->payment_method_id, $data[1]['payment_method_id']);
        $this->assertEquals($deleted_invoice->paid_at, $data[1]['paid_at']);
        $this->assertEquals($deleted_invoice->gross_counted, $data[1]['gross_counted']);
        $this->assertEquals($deleted_invoice->last_printed_at, $data[1]['last_printed_at']);
        $this->assertEquals($deleted_invoice->last_send_at, $data[1]['last_send_at']);
        $this->assertEquals($deleted_invoice->created_at, $data[1]['created_at']);
        $this->assertEquals($deleted_invoice->updated_at, $data[1]['updated_at']);
        $this->assertEquals($deleted_invoice->deleted_at, $data[1]['deleted_at']);
    }

    /** @test */
    public function test_index_with_parent_correct_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        $invoices = factory(Invoice::class, 5)->create([
            'company_id' => $company->id,
        ]);
        factory(ModelInvoiceInvoice::class)->create([
            'parent_id' => $invoices[0]->id,
            'node_id' => $invoices[1]->id,
        ]);
        factory(ModelInvoiceInvoice::class)->create([
            'parent_id' => $invoices[0]->id,
            'node_id' => $invoices[4]->id,
        ]);
        factory(ModelInvoiceInvoice::class)->create([
            'parent_id' => $invoices[2]->id,
            'node_id' => $invoices[0]->id,
        ]);
        factory(ModelInvoiceInvoice::class)->create([
            'parent_id' => $invoices[4]->id,
            'node_id' => $invoices[2]->id,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id)->assertResponseOk();

        $response = $this->decodeResponseJson();
        $data = $response['data'];

        // verify number of invoices
        $this->assertSame(5, count($data));

        // verify 1st invoice related invoices
        $this->assertSame($invoices[0]->id, $data[0]['id']);
        $this->assertSame(3, count($data[0]['invoices']['data']));
        $this->assertEquals([
            [
                'id' => $invoices[2]->id,
                'number' => $invoices[2]->number,
            ],
            [
                'id' => $invoices[1]->id,
                'number' => $invoices[1]->number,
            ],
            [
                'id' => $invoices[4]->id,
                'number' => $invoices[4]->number,
            ],
        ], $data[0]['invoices']['data']);

        // verify 2nd invoice related invoices
        $this->assertSame($invoices[1]->id, $data[1]['id']);
        $this->assertSame(1, count($data[1]['invoices']['data']));
        $this->assertEquals([
            [
                'id' => $invoices[0]->id,
                'number' => $invoices[0]->number,
            ],
        ], $data[1]['invoices']['data']);

        // verify 3rd invoice related invoices
        $this->assertSame($invoices[2]->id, $data[2]['id']);
        $this->assertSame(2, count($data[2]['invoices']['data']));
        $this->assertEquals([
            [
                'id' => $invoices[4]->id,
                'number' => $invoices[4]->number,
            ],
            [
                'id' => $invoices[0]->id,
                'number' => $invoices[0]->number,
            ],
        ], $data[2]['invoices']['data']);

        // verify 4th invoice related invoices
        $this->assertSame($invoices[3]->id, $data[3]['id']);
        $this->assertSame(0, count($data[3]['invoices']['data']));

        // verify 5th invoice related invoices
        $this->assertSame($invoices[4]->id, $data[4]['id']);
        $this->assertSame(2, count($data[4]['invoices']['data']));
        $this->assertEquals([
            [
                'id' => $invoices[0]->id,
                'number' => $invoices[0]->number,
            ],
            [
                'id' => $invoices[2]->id,
                'number' => $invoices[2]->number,
            ],
        ], $data[4]['invoices']['data']);
        $this->assertFalse($data[0]['is_editable']);
        $this->assertFalse($data[1]['is_editable']);
        $this->assertFalse($data[2]['is_editable']);
        $this->assertTrue($data[3]['is_editable']);
        $this->assertFalse($data[4]['is_editable']);
    }

    /** @test */
    public function test_index_with_status_all_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
            'paid_at' => null,
        ]);
        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'paid_at' => Carbon::now(),
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&status=all');
        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(5, count($data));
    }

    /** @test */
    public function test_index_with_status_paid_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
            'paid_at' => null,
        ]);
        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'paid_at' => Carbon::now(),
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&status=paid');
        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(2, count($data));
    }

    /** @test */
    public function test_index_with_status_not_paid_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
            'paid_at' => null,
        ]);
        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'paid_at' => null,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'paid_at' => Carbon::now(),
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&status=not_paid');
        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(3, count($data));
    }

    /** @test */
    public function test_index_with_status_paid_late_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-01-15',
            'payment_term_days' => 4,
            'paid_at' => '2017-01-16 02:00:00',
        ]);
        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-01-15',
            'payment_term_days' => 4,
            'paid_at' => '2017-01-22 02:00:00',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&status=paid_late');
        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(2, count($data));
    }

    /** @test */
    public function test_index_with_status_deleted_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'deleted_at' => '2017-01-22 02:00:00',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&status=deleted');
        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(2, count($data));
    }

    /** @test */
    public function test_index_with_status_not_deleted_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'deleted_at' => '2017-01-22 02:00:00',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&status=not_deleted');
        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));
    }

    /** @test */
    public function test_index_with_status_invalid_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/invoices?selected_company_id=' . $company->id . '&status=abc');

        $this->verifyValidationResponse(['status']);
    }

    /** @test */
    public function test_index_with_date_start_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'issue_date' => Carbon::now()->subMonths(60)->toDateString(),
        ]);
        $invoices_new = factory(Invoice::class, 4)->create([
            'company_id' => $company->id,
            'issue_date' => Carbon::now()->subDays(10)->toDateString(),
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&date_start=' .
            Carbon::now()->subDays(10)->toDateString());

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($invoices_new->count(), count($data));
    }

    /** @test */
    public function test_index_with_date_start_invalid_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/invoices?selected_company_id=' . $company->id . '&date_start=abc');

        $this->verifyValidationResponse(['date_start']);
    }

    /** @test */
    public function test_index_with_date_end_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        $invoices_old = factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'issue_date' => Carbon::now()->subMonths(60)->toDateString(),
        ]);
        factory(Invoice::class, 4)->create([
            'company_id' => $company->id,
            'issue_date' => Carbon::now()->subDays(10)->toDateString(),
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&date_end=' .
            Carbon::now()->subMonths(60)->toDateString());

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($invoices_old->count(), count($data));
    }

    /** @test */
    public function test_index_with_date_end_invalid_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/invoices?selected_company_id=' . $company->id . '&date_end=abc');

        $this->verifyValidationResponse(['date_end']);
    }

    /** @test */
    public function test_index_with_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        $invoices = factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&id=' .
            $invoices[0]->id);

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));
        $this->assertEquals($invoices[0]->id, $data[0]['id']);
    }

    /** @test */
    public function test_index_with_invalid_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        $invoices = factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&id=' .
            ($invoices[0]->id + 10));

        $this->verifyValidationResponse(['id']);
    }

    /** @test */
    public function test_index_with_number_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 754345,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&number=' .
            $invoice->number);

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));
        $this->assertEquals($invoice->number, $data[0]['number']);
    }

    /** @test */
    public function test_index_with_number_like_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 'abc1974',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 'abc1234',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 'abc1297az',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&number=abc12');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(2, count($data));
        $this->assertEquals('abc1234', $data[0]['number']);
        $this->assertEquals('abc1297az', $data[1]['number']);
    }

    /** @test */
    public function test_index_it_accepts_any_character_in_number_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 754345,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&number=01/2017')
            ->assertResponseOk();
    }

    /** @test */
    public function test_index_with_contractor_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
        ]);
        $invoice->contractor_id = $contractor->id;
        $invoice->save();

        $this->get('/invoices?selected_company_id=' . $company->id . '&contractor_id=' .
            $contractor->id)->assertResponseStatus(200);

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));
        $this->assertEquals($invoice->contractor_id, $data[0]['contractor_id']);
    }

    /** @test */
    public function test_index_with_invalid_contractor_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $this->user->id,
        ]);
        $invoice->contractor_id = $invoice_contractor->id;
        $invoice->save();

        $this->get('/invoices?selected_company_id=' . $company->id . '&contractor_id=' .
            ($invoice->contractor_id + 5));

        $this->verifyValidationResponse(['contractor_id']);
    }

    /** @test */
    public function test_index_with_drawer_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'drawer_id' => $this->user->id,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&drawer_id=' .
            $invoice->drawer_id);

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));
        $this->assertEquals($invoice->drawer_id, $data[0]['drawer_id']);
    }

    /** @test */
    public function test_index_with_invalid_drawer_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'drawer_id' => $this->user->id,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&drawer_id=' .
            ($invoice->drawer_id + 5));

        $this->verifyValidationResponse(['drawer_id']);
    }

    /** @test */
    public function test_index_with_id_asc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        $first_invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=id');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($first_invoice->id, $data[0]['id']);
    }

    /** @test */
    public function test_index_with_id_desc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);
        $last_invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=-id');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($last_invoice->id, $data[0]['id']);
    }

    /** @test */
    public function test_index_with_number_asc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 1,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 2,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=number');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, $data[0]['number']);
    }

    /** @test */
    public function test_index_with_number_desc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 1,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 2,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=-number');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(2, $data[0]['number']);
    }

    /** @test */
    public function test_index_with_order_number_asc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'order_number' => 1,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'order_number' => 2,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id .
            '&sort=order_number');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, $data[0]['order_number']);
    }

    /** @test */
    public function test_index_with_order_number_desc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'order_number' => 1,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'order_number' => 2,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id .
            '&sort=-order_number');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(2, $data[0]['order_number']);
    }

    /** @test */
    public function test_index_with_sale_date_asc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => '2015-05-25',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => '2017-02-20',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=sale_date');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals('2015-05-25', $data[0]['sale_date']);
    }

    /** @test */
    public function test_index_with_sale_date_desc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => '2015-05-25',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => '2017-02-20',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=-sale_date');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals('2017-02-20', $data[0]['sale_date']);
    }

    /** @test */
    public function test_index_with_issue_date_asc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2015-05-25',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-20',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=issue_date');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals('2015-05-25', $data[0]['issue_date']);
    }

    /** @test */
    public function test_index_with_issue_date_desc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2015-05-25',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-20',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=-issue_date');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals('2017-02-20', $data[0]['issue_date']);
    }

    /** @test */
    public function test_index_with_price_net_asc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1520,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 2820,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=price_net');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(15.20, $data[0]['price_net']);
    }

    /** @test */
    public function test_index_with_price_net_desc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1520,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 2820,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=-price_net');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(28.20, $data[0]['price_net']);
    }

    /** @test */
    public function test_index_with_price_gross_asc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 1520,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 2820,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=price_gross');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(15.20, $data[0]['price_gross']);
    }

    /** @test */
    public function test_index_with_price_gross_desc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 1520,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 2820,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id .
            '&sort=-price_gross');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(28.20, $data[0]['price_gross']);
    }

    /** @test */
    public function test_index_with_payment_left_asc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'payment_left' => 1254,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'payment_left' => 1795,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id .
            '&sort=payment_left');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(12.54, $data[0]['payment_left']);
    }

    /** @test */
    public function test_index_with_payment_left_desc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'payment_left' => 1254,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'payment_left' => 1795,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id .
            '&sort=-payment_left');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(17.95, $data[0]['payment_left']);
    }

    /** @test */
    public function test_index_with_created_at_asc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'created_at' => '2015-05-25 15:48:19',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'created_at' => '2017-09-17 18:42:14',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=created_at');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals('2015-05-25 15:48:19', $data[0]['created_at']);
    }

    /** @test */
    public function test_index_with_created_at_desc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'created_at' => '2015-05-25 15:48:19',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'created_at' => '2017-09-17 18:42:14',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=-created_at');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals('2017-09-17 18:42:14', $data[0]['created_at']);
    }

    /** @test */
    public function test_index_with_updated_at_asc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'updated_at' => '2015-05-25 15:48:19',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'updated_at' => '2017-09-17 18:42:14',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=updated_at');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals('2015-05-25 15:48:19', $data[0]['updated_at']);
    }

    /** @test */
    public function test_index_with_updated_at_desc_sort()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'updated_at' => '2015-05-25 15:48:19',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'updated_at' => '2017-09-17 18:42:14',
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&sort=-updated_at');

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals('2017-09-17 18:42:14', $data[0]['updated_at']);
    }

    /** @test */
    public function test_index_with_contractor_vatin_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $this->user->id,
            'vatin' => '123456789',
        ]);
        $invoice->contractor_id = $invoice_contractor->id;
        $invoice->save();

        $this->get('/invoices?selected_company_id=' . $company->id . '&contractor=45678');
        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));
        $this->assertStringContainsString(
            '123456789',
            $data[0]['invoice_contractor']['data']['vatin']
        );
    }

    /** @test */
    public function test_index_with_contractor_name_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $this->user->id,
            'name' => 'example_name',
        ]);
        $invoice->contractor_id = $invoice_contractor->id;
        $invoice->save();

        $this->get('/invoices?selected_company_id=' . $company->id . '&contractor=mple_na');
        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));
        $this->assertStringContainsString(
            'example_name',
            $data[0]['invoice_contractor']['data']['name']
        );
    }

    /** @test */
    public function index_proforma_id_filter_validation_error()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);

        $invoice_proforma = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $invoice_advance = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'proforma_id' => $invoice_proforma->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id
            . '&proforma_id=' . $invoice_advance->id)->seeStatusCode(422);

        $this->verifyValidationResponse(['proforma_id']);
    }

    /** @test */
    public function index_proforma_id_filter_with_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);

        $invoice_proforma = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $invoice_advance = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'proforma_id' => $invoice_proforma->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
        ]);

        $this->get('/invoices?selected_company_id=' . $company->id
            . '&proforma_id=' . $invoice_proforma->id)->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));
        $this->assertEquals($invoice_advance->id, $data[0]['id']);
    }

    /** @test */
    public function index_invoice_type_id_filter_validation_error()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/invoices?selected_company_id=' . $company->id
            . '&invoice_type_id=923')->seeStatusCode(422);

        $this->verifyValidationResponse(['invoice_type_id']);
    }

    /** @test */
    public function index_invoice_type_id_filter_with_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);

        $invoice_proforma = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $invoice_advance = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'proforma_id' => $invoice_proforma->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
        ]);

        $this->get(
            '/invoices?selected_company_id=' . $company->id
            . '&invoice_type_id=' . InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id
        )->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));
        $this->assertEquals($invoice_proforma->id, $data[0]['id']);
    }

    /** @test */
    public function index_invoice_registry_id_filter_validation_error()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/invoices?selected_company_id=' . $company->id
            . '&invoice_registry_id=0')->seeStatusCode(422);

        $this->verifyValidationResponse(['invoice_registry_id']);
    }

    /** @test */
    public function index_invoice_registry_id_other_company_filter_validation_error()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'invoice_registry_id' => InvoiceRegistry::create()->id,
        ]);

        $this->get(
            '/invoices?selected_company_id=' . $company->id
            . '&invoice_registry_id=' . $invoice[0]->invoice_registry_id
        )->seeStatusCode(422);

        $this->verifyValidationResponse(['invoice_registry_id']);
    }

    /** @test */
    public function index_invoice_registry_id_filter_with_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $registry = factory(InvoiceRegistry::class)->create(['company_id' => $company->id]);

        $invoices = factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'invoice_registry_id' => $registry->id,
        ]);

        factory(Invoice::class)->create(['company_id' => $company->id]);

        $this->get('/invoices?selected_company_id=' . $company->id . '&invoice_registry_id=' . $registry->id)
            ->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(2, count($data));
        $this->assertEquals($invoices[0]->id, $data[0]['id']);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_indexPdf_get_pdf_with_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);
        auth()->loginUsingId($this->user->id);

        $this->createInvoices($company);

        ob_start();

        $this->get('/invoices/pdf?selected_company_id=' . $company->id)->assertResponseOk();

        $pdf_content = ob_get_clean();

        $array = [
            'Lista faktur',
            'Data',
            'Wartość',
            'Wartość',
            'Termin',
            'L.p.',
            'Numer',
            'Kontrahent',
            'Typ',
            'wystawienia',
            'netto',
            'brutto',
            'płatności',

            'John Doe Company',
            'Faktura',
            '1',
            '1/07/2017',
            '2017-07-26',
            '12 345,00',
            '16 541,00',
            '2017-08-02',
            'Ltd',
            'VAT',

            'John Doe Company',
            'Faktura',
            '2',
            'KOR/1/07/2017',
            '2017-07-26',
            '12 345,00',
            '16 541,00',
            '2017-08-02',
            'Ltd',
            'VAT',

            'John Doe Company',
            'Faktura',
            '3',
            'TESTOWA/KOR/1/07/2017',
            '2017-07-26',
            '12 345,00',
            '16 541,00',
            '2017-08-02',
            'Ltd',
            'VAT',

            'John Doe Company',
            'Faktura',
            '4',
            'TESTOWA/KOR/2/07/2017',
            '2017-07-26',
            '12 345,00',
            '16 541,00',
            '2017-08-02',
            'Ltd',
            'VAT',

            'John Doe Company',
            'Faktura',
            '5',
            '2/07/2017',
            '2017-07-19',
            '12 345,00',
            '16 541,00',
            '2017-07-26',
            'Ltd',
            'VAT',

            'John Doe Company',
            'Faktura',
            '6',
            '3/07/2017',
            '2017-07-21',
            '12 345,00',
            '16 541,00',
            '2017-07-28',
            'Ltd',
            'VAT',

            'Suma netto:',
            '74 070,00',
            'Suma brutto:',
            '99 246,00',

            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Strona (1/1)',
        ];

        $this->assertPdf($pdf_content, $array);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function indexPdf_get_pdf_with_filters_with_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);
        auth()->loginUsingId($this->user->id);

        $this->createInvoices($company);

        ob_start();

        $this->get(
            '/invoices/pdf?selected_company_id=' . $company->id
            . '&contractor=John'
            . '&date_start=2017-07-20'
            . '&date_end=2017-07-30'
            . '&status=paid'
        )->assertResponseOk();

        $pdf_content = ob_get_clean();

        $array = [
            'Lista faktur',
            'Od',
            '2017-07-20',
            'do',
            '2017-07-30',
            'Ze statusem:',
            'opłacone',
            'Dla kontrahenta',
            'John',
            'Data',
            'Wartość',
            'Wartość',
            'Termin',
            'L.p.',
            'Numer',
            'Kontrahent',
            'Typ',
            'wystawienia', // Data
            'netto', // Wartość
            'brutto', // Wartość
            'płatności', // Termin
            'John Doe',
            'Faktura',
            '1',
            'TESTOWA/KOR/2/07/2017',
            '2017-07-26',
            '12 345,00',
            '16 541,00',
            '2017-08-02',
            'Ltd',
            'VAT',
            '2',
            '3/07/2017',
            '2017-07-21',
            '12 345,00',
            '16 541,00',
            '2017-07-28',
            'Ltd',
            'VAT',

            'Suma netto:',
            '24 690,00',
            'Suma brutto:',
            '33 082,00',
        ];

        $this->assertPdf($pdf_content, $array);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function indexPdf_it_doesnt_show_deleted_invoice_on_invoice_list()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);
        auth()->loginUsingId($this->user->id);

        $this->createInvoices($company);

        $invoice = Invoice::where('number', '3/07/2017')->firstOrFail();
        $invoice->delete();

        ob_start();

        $this->get('/invoices/pdf?selected_company_id=' . $company->id)->assertResponseOk();

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // set directory and file names
            $directory = storage_path('tests');
            $file = storage_path('tests/invoice.pdf');
            $text_file = storage_path('tests/invoice.txt');

            // set up directory and files and make sure files don't exist
            if (! File::exists($directory)) {
                File::makeDirectory($directory, 0777);
            }
            if (File::exists($file)) {
                File::delete($file);
            }
            if (File::exists($text_file)) {
                File::delete($text_file);
            }

            // save PDF file content
            File::put($file, $pdf_content);
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            $text_content = file_get_contents($text_file);
            $this->assertFalse(str_contains($text_content, '3/07/2017'));
            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function indexPdf_it_show_deleted_invoice_on_invoice_list_with_deleted_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);
        auth()->loginUsingId($this->user->id);

        $this->createInvoices($company);

        $invoice = Invoice::where('number', '3/07/2017')->firstOrFail();
        $invoice->delete();

        ob_start();

        $this->get('/invoices/pdf?selected_company_id=' . $company->id . '&status=deleted')->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertPdf($pdf_content, ['3/07/2017']);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function indexPdf_get_pdf_with_filters_by_no_paid_with_extra_load_column()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);
        auth()->loginUsingId($this->user->id);

        $this->createInvoices($company);

        Invoice::whereDate('issue_date', '>=', '2017-07-20')->update([
           'payment_left' => 100000000,
        ]);

        ob_start();

        $this->get(
            '/invoices/pdf?selected_company_id=' . $company->id
            . '&contractor=John'
            . '&date_start=2017-07-20'
            . '&date_end=2017-07-30'
            . '&status=' . FilterOption::NOT_PAID
        )->assertResponseOk();

        $pdf_content = ob_get_clean();

        $array = [
            'Termin',
            'Do zapłaty',
            'płatności',
            '1 000 000,00',
            '1 000 000,00',
            '1 000 000,00',
            'Suma netto:',
            '37 035,00',
            'Suma brutto:',
            '49 623,00',
            'Pozostaje do zapłaty:',
            '3 000 000,00',
        ];

        $this->assertPdf($pdf_content, $array);
    }

    /**
     * Create invoices.
     *
     * @param $company
     */
    protected function createInvoices($company)
    {
        $contractor = factory(Contractor::class)->create(['name' => 'John Doe Company Ltd']);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'number' => '1/07/2017',
            'created_at' => '2017-07-26',
            'issue_date' => '2017-07-26',
            'price_net' => 1234500,
            'price_gross' => 1654100,
            'payment_term_days' => 7,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'number' => 'KOR/1/07/2017',
            'created_at' => '2017-07-26',
            'issue_date' => '2017-07-26',
            'price_net' => 1234500,
            'price_gross' => 1654100,
            'payment_term_days' => 7,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'number' => 'TESTOWA/KOR/1/07/2017',
            'created_at' => '2017-07-26',
            'issue_date' => '2017-07-26',
            'price_net' => 1234500,
            'price_gross' => 1654100,
            'payment_term_days' => 7,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'number' => 'TESTOWA/KOR/2/07/2017',
            'created_at' => '2017-07-26',
            'issue_date' => '2017-07-26',
            'price_net' => 1234500,
            'price_gross' => 1654100,
            'payment_term_days' => 7,
            'paid_at' => '2017-07-26',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'number' => '2/07/2017',
            'created_at' => '2017-07-19',
            'issue_date' => '2017-07-19',
            'price_net' => 1234500,
            'price_gross' => 1654100,
            'payment_term_days' => 7,
            'paid_at' => '2011-07-26',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'number' => '3/07/2017',
            'created_at' => '2017-07-21',
            'issue_date' => '2017-07-21',
            'price_net' => 1234500,
            'price_gross' => 1654100,
            'payment_term_days' => 7,
            'paid_at' => '2011-07-26',
        ]);
        Invoice::all()->each(function ($invoice) use ($contractor) {
            factory(InvoiceContractor::class)->create([
                'invoice_id' => $invoice->id,
                'contractor_id' => $contractor->id,
                'name' => 'John Doe Company Ltd',
            ]);
        });
    }

    /**
     * @param $file
     * @param $pdf_content
     * @param $text_file
     * @param $array
     * @param $directory
     */
    protected function assertPdf($pdf_content, $array)
    {
        if (! config('test_settings.enable_test_pdf')) {
            return;
        }

        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/invoice.pdf');
        $text_file = storage_path('tests/invoice.txt');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }
        if (File::exists($text_file)) {
            File::delete($text_file);
        }

        File::put($file, $pdf_content);
        exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
        $text_content = file_get_contents($text_file);
        $this->assertContainsOrdered($array, $text_content);
        File::deleteDirectory($directory);
    }
}
