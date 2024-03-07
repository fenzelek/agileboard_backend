<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController;

use App\Models\Db\CashFlow;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceFinalAdvanceTaxReport;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoicePayment;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\InvoiceType;
use App\Models\Db\Package;
use App\Models\Db\PaymentMethod;
use App\Models\Db\VatRate;
use App\Models\Other\ModuleType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\InvoiceRegistry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\Feature\App\Modules\SaleInvoice\Helpers\FinancialEnvironment as FinancialEnvironmentTrait;
use Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload\FinancialEnvironment;
use File;

class FinalAdvanceTest extends FinancialEnvironment
{
    use DatabaseTransactions, FinancialEnvironmentTrait;

    public function setUp():void
    {
        parent::setUp();
        $this->registry = factory(InvoiceRegistry::class)->create();
        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);
    }

    /** @test */
    public function store_validation_error_lack_proforma()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'proforma_id',
        ]);
    }

    /** @test */
    public function store_validation_error_lack_proforma_item_id()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma) = $this->setIncomingDataForFinalAdvanceInvoice();
        Arr::forget($incoming_data, 'items.0.proforma_item_id');

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.proforma_item_id',
        ]);
    }

    /** @test */
    public function store_validation_error_lack_advance_taxes()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma) = $this->setIncomingDataForFinalAdvanceInvoice();
        array_forget($incoming_data, 'advance_taxes');
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'advance_taxes',
        ]);
    }

    /** @test */
    public function store_validation_error_lack_item_advance_taxes()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma) = $this->setIncomingDataForFinalAdvanceInvoice();
        array_forget($incoming_data, 'advance_taxes.0.vat_rate_id');
        array_forget($incoming_data, 'advance_taxes.0.price_net');
        array_forget($incoming_data, 'advance_taxes.0.price_gross');
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'advance_taxes.0.vat_rate_id',
            'advance_taxes.0.price_net',
            'advance_taxes.0.price_gross',
        ]);
    }

    /** @test */
    public function store_disabled_advance_module_error()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, false);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_type_id',
        ]);
    }

    /** @test */
    public function store_always_add_payment_for_invoice()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma, $proforma_items, $advance_invoice, $advance_invoice_item) = $this->setIncomingDataForFinalAdvanceInvoice();

        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $incoming_data['payment_method_id'] = $payment_method->id;

        $init_payments_count = InvoicePayment::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_payments_count + 1, InvoicePayment::count());
        $invoice = Invoice::latest('id')->first();
        $invoice_payment = InvoicePayment::latest('id')->first();
        $this->assertSame(2000, $invoice_payment->amount);
        $this->assertSame(0, $invoice->payment_left);
        $this->assertSame($invoice->id, $invoice_payment->invoice_id);
        $this->assertSame($payment_method->id, $invoice_payment->payment_method_id);
        $this->assertFalse((bool) $invoice_payment->special_partial_payment);
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);
    }

    /** @test */
    public function store_always_add_cashflow_for_invoice_less_on_paid_in_advance()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma, $proforma_items, $advance_invoice, $advance_invoice_item) = $this->setIncomingDataForFinalAdvanceInvoice();

        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $incoming_data['payment_method_id'] = $payment_method->id;
        $init_payments_count = Cashflow::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_payments_count + 1, CashFlow::count());
        $invoice = Invoice::latest('id')->first();
        $cashflow = CashFlow::latest('id')->first();
        $this->assertSame(2000, $cashflow->amount);
        $this->assertSame($company->id, $cashflow->company_id);
        $this->assertSame($invoice->id, $cashflow->invoice_id);
        $this->assertNull($cashflow->receipt_id);
        $this->assertSame(CashFlow::DIRECTION_IN, $cashflow->direction);
        $this->assertTrue((bool) $cashflow->cashless);
        $this->assertEmpty($cashflow->description);
        $this->assertSame($incoming_data['issue_date'], $cashflow->flow_date);
    }

    /** @test */
    public function store_invoice_number_queue_continue_vat_queue()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma, $proforma_items, $advance_invoice, $advance_invoice_item) = $this->setIncomingDataForFinalAdvanceInvoice();

        $invoice_first_advance = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'order_number' => 1,
            'number' => '1/01/2017',
            'invoice_registry_id' => InvoiceRegistry::first()->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);
        $invoice_vat = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'order_number' => 2,
            'number' => '2/01/2017',
            'invoice_registry_id' => InvoiceRegistry::first()->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice_advance = Invoice::latest('id')->first();
        $this->assertSame(5, Invoice::count());
        $this->assertSame(2, $invoice_advance->order_number);
        $this->assertSame('ZAL/' . $this->registry->prefix . '/2/01/2017', $invoice_advance->number);
    }

    /** @test */
    public function store_correct_data_in_database()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma, $proforma_items) = $this->setIncomingDataForFinalAdvanceInvoice();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);
        $invoice_final = Invoice::latest('id')->first();
        $this->assertSame($init_invoice_count + 1, Invoice::count());
        $this->assertSame(1, $invoice_final->order_number);
        $this->assertSame('ZAL/' . $this->registry->prefix . '/1/01/2017', $invoice_final->number);

        $invoice_expect_data = $this->invoiceExpectData();

        $this->assertSame($this->user->id, $invoice_final->drawer_id);
        $this->assertSame($company->id, $invoice_final->company_id);
        $this->assertNull($invoice_final->corrected_invoice_margin_id);
        $this->assertNull($invoice_final->correction_type);
        $this->assertEquals($incoming_data['sale_date'], $invoice_final->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $invoice_final->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $invoice_final->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $invoice_final->contractor_id);
        $this->assertSame(InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id, $invoice_final->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $invoice_final->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice_final->price_gross);
        $this->assertSame($invoice_expect_data['vat_sum'], $invoice_final->vat_sum);
        $this->assertSame(0, $invoice_final->payment_left);
        $this->assertSame($incoming_data['payment_method_id'], $invoice_final->payment_method_id);
        $this->assertNull($invoice_final->last_printed_at);
        $this->assertNull($invoice_final->last_send_at);
        $this->assertNotNull($invoice_final->created_at);
        $this->assertNull($invoice_final->update_at);
        $this->assertEquals(Carbon::parse($incoming_data['issue_date'])->toDateTimeString(), $invoice_final->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $invoice_final->gross_counted);
        $this->assertNull($invoice_final->invoice_margin_procedure_id);
        $this->assertNull($invoice_final->invoice_reverse_charge_id);
        $this->assertSame($proforma->id, $invoice_final->proforma_id);
        $tax_report = InvoiceTaxReport::latest('id')->first();
        $invoice_items_expect = $this->invoiceExpectData_gross_count()['advance_taxes'];
        $this->assertSame($invoice_items_expect[0]['price_net'], $tax_report->price_net);
        $this->assertSame($invoice_items_expect[0]['price_gross'], $tax_report->price_gross);
        $tax_report = InvoiceFinalAdvanceTaxReport::latest('id')->first();
        $invoice_items_expect = $this->invoiceExpectData_gross_count()['taxes'];
        $this->assertSame($invoice_items_expect[0]['price_net'], $tax_report->price_net);
        $this->assertSame($invoice_items_expect[0]['price_gross'], $tax_report->price_gross);

        $items = InvoiceItem::latest('id')->skip(3)->first();
        $invoice_items_expect = $this->invoiceExpectData()['items'];
        $this->assertSame($invoice_items_expect[0]['price_net_sum'], $items->price_net_sum);
        $this->assertSame($invoice_items_expect[0]['price_gross_sum'], $items->price_gross_sum);
        $this->assertSame($invoice_items_expect[0]['vat_sum'], $items->vat_sum);
        $this->assertSame($proforma_items[0]->id, $items->proforma_item_id);
        $items = InvoiceItem::latest('id')->skip(2)->first();
        $invoice_items_expect = $this->invoiceExpectData()['items'];
        $this->assertSame($invoice_items_expect[1]['price_net_sum'], $items->price_net_sum);
        $this->assertSame($invoice_items_expect[1]['price_gross_sum'], $items->price_gross_sum);
        $this->assertSame($invoice_items_expect[1]['vat_sum'], $items->vat_sum);
        $this->assertSame($proforma_items[1]->id, $items->proforma_item_id);
        $items = InvoiceItem::latest('id')->skip(1)->first();
        $invoice_items_expect = $this->invoiceExpectData()['items'];
        $this->assertSame($invoice_items_expect[2]['price_net_sum'], $items->price_net_sum);
        $this->assertSame($invoice_items_expect[2]['price_gross_sum'], $items->price_gross_sum);
        $this->assertSame($invoice_items_expect[2]['vat_sum'], $items->vat_sum);
        $this->assertSame($proforma_items[2]->id, $items->proforma_item_id);
        $items = InvoiceItem::latest('id')->first();
        $invoice_items_expect = $this->invoiceExpectData()['items'];
        $this->assertSame($invoice_items_expect[3]['price_net_sum'], $items->price_net_sum);
        $this->assertSame($invoice_items_expect[3]['price_gross_sum'], $items->price_gross_sum);
        $this->assertSame($invoice_items_expect[3]['vat_sum'], $items->vat_sum);
        $this->assertSame($proforma_items[3]->id, $items->proforma_item_id);
    }

    /** @test */
    public function store_has_proforma_node()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma) = $this->setIncomingDataForFinalAdvanceInvoice();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice = Invoice::latest('id')->first();
        $this->assertSame($incoming_data['proforma_id'], $invoice->nodeInvoices()->first()->id);
        $this->assertSame($incoming_data['proforma_id'], $invoice->proforma->id);
    }

    /** @test */
    public function store_response_correct()
    {
        list($company, $incoming_data, $init_invoice_count) = $this->setIncomingDataForFinalAdvanceInvoice();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($invoice->id, $json_data['id']);
        $this->assertSame($invoice->number, $json_data['number']);
        $this->assertSame($invoice->order_number, $json_data['order_number']);
        $this->assertSame($invoice->invoice_registry_id, $json_data['invoice_registry_id']);
        $this->assertSame($invoice->drawer_id, $json_data['drawer_id']);
        $this->assertSame($invoice->company_id, $json_data['company_id']);
        $this->assertSame($invoice->contractor_id, $json_data['contractor_id']);
        $this->assertSame($invoice->corrected_invoice_id, $json_data['corrected_invoice_id']);
        $this->assertSame($invoice->correction_type, $json_data['correction_type']);
        $this->assertSame($invoice->sale_date, $json_data['sale_date']);
        $this->assertSame($invoice->issue_date, $json_data['issue_date']);
        $this->assertSame($invoice->invoice_type_id, $json_data['invoice_type_id']);
        $this->assertSame(10.1, $json_data['price_net']);
        $this->assertSame(11.11, $json_data['price_gross']);
        $this->assertSame(4.6, $json_data['vat_sum']);
        $this->assertNull($json_data['payment_left']);
        $this->assertSame($invoice->payment_method_id, $json_data['payment_method_id']);
        $this->assertSame(Carbon::parse($invoice->paid_at)->toDateTimeString(), $json_data['paid_at']);
        $this->assertEquals($invoice->gross_counted, $json_data['gross_counted']);
        $this->assertNull($json_data['last_printed_at']);
        $this->assertNull($json_data['last_send_at']);
        $this->assertEquals($invoice->created_at, $json_data['created_at']);
        $this->assertEquals($invoice->updated_at, $json_data['updated_at']);
        $this->assertNull($json_data['invoice_margin_procedure_id']);
        $this->assertNull($json_data['invoice_reverse_charge_id']);
    }

    /** @test */
    public function update_disabled_invoice_advance_module()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, false);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id,
        ]);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 0,
            'payment_left' => 0,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'id',
        ]);
    }

    /** @test */
    public function update_no_block_by_proforma()
    {
        list($now, $company, $payment_method, $invoice, $items_data, $taxes_data, $invoice_type_advance, $proforma_items, $advance_taxes_data) = $this->setIncomingDataForUpdateFinalAdvanceInvoice();

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.11,
            'vat_sum' => 10,
            'payment_term_days' => 0,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'advance_taxes' => $advance_taxes_data,
        ])->assertResponseStatus(200);
    }

    /** @test */
    public function update_data_in_database()
    {
        list($now, $company, $payment_method, $invoice, $items_data, $taxes_data, $invoice_type_advance, $proforma_items, $advance_taxes_data) = $this->setIncomingDataForUpdateFinalAdvanceInvoice();

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $invoice_tax_reports = InvoiceTaxReport::count();
        $invoice_final_advance_tax_reports = InvoiceFinalAdvanceTaxReport::count();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.11,
            'vat_sum' => 10,
            'payment_term_days' => 0,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'advance_taxes' => $advance_taxes_data,
        ])->assertResponseStatus(200);
        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($taxes_data), InvoiceTaxReport::count());
        $this->assertSame(count($advance_taxes_data), InvoiceFinalAdvanceTaxReport::count());
        $this->assertSame(count($items_data), $invoice->items()->count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_items_fresh = $invoice_fresh->items;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertnull($invoice_fresh->corrected_invoice_id);
        $this->assertSame('2017-02-09', $invoice_fresh->sale_date);
        $this->assertEquals($now->toDateTimeString(), $invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice_type_advance->id, $invoice_fresh->invoice_type_id);
        $this->assertSame(1234, $invoice_fresh->price_net);
        $this->assertSame(1411, $invoice_fresh->price_gross);
        $this->assertSame(1000, $invoice_fresh->vat_sum);
        $this->assertSame(0, $invoice_fresh->payment_left);
        $this->assertSame(0, $invoice_fresh->payment_term_days);
        $this->assertSame($payment_method->id, $invoice_fresh->payment_method_id);
        $this->assertSame(1, $invoice_fresh->gross_counted);
        $this->assertNull($invoice_fresh->invoice_margin_procedure_id);
        $this->assertNull($invoice_fresh->invoice_reverse_charge_id);
    }

    /** @test */
    public function update_check_response_json()
    {
        list($now, $company, $payment_method, $invoice, $items_data, $taxes_data, $invoice_type_advance, $proforma_items, $advance_taxes_data) = $this->setIncomingDataForUpdateFinalAdvanceInvoice();

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.14,
            'vat_sum' => 10,
            'payment_term_days' => 0,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'advance_taxes' => $advance_taxes_data,
        ])->assertResponseStatus(200);

        // Fresh data
        $invoice_fresh = $invoice->fresh();

        $response = $this->decodeResponseJson()['data'];
        $this->assertSame($invoice_fresh->number, $response['number']);
        $this->assertSame($invoice_fresh->order_number, $response['order_number']);
        $this->assertSame($invoice_fresh->invoice_registry_id, $response['invoice_registry_id']);
        $this->assertSame($invoice_fresh->drawer_id, $response['drawer_id']);
        $this->assertSame($invoice_fresh->company_id, $response['company_id']);
        $this->assertSame($invoice_fresh->contractor_id, $response['contractor_id']);
        $this->assertnull($response['corrected_invoice_id']);
        $this->assertSame('2017-02-09', $response['sale_date']);
        $this->assertSame($now->toDateTimeString(), $response['paid_at']);
        $this->assertSame('2017-02-02', $response['issue_date']);
        $this->assertSame($invoice_type_advance->id, $response['invoice_type_id']);
        $this->assertSame(12.34, $response['price_net']);
        $this->assertSame(14.14, $response['price_gross']);
        $this->assertSame(10, $response['vat_sum']);
        $this->assertNull($response['payment_left']);
        $this->assertSame(0, $response['payment_term_days']);
        $this->assertSame($payment_method->id, $response['payment_method_id']);
        $this->assertSame(1, $response['gross_counted']);
        $this->assertNull($response['invoice_margin_procedure_id']);
        $this->assertNull($response['invoice_reverse_charge_id']);
    }

    /** @test */
    public function update_always_update_payment_cashflow_for_invoice()
    {
        list($now, $company, $payment_method, $invoice, $items_data, $taxes_data, $invoice_type_advance, $proforma_items, $advance_taxes_data) = $this->setIncomingDataForUpdateFinalAdvanceInvoice();

        $invoice->update([
            'price_gross' => 1000,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'amount' => 1000,
        ]);
        $init_invoice_payments = InvoicePayment::count();

        $cashflow = factory(CashFlow::class)->create([
            'invoice_id' => $invoice->id,
            'direction' => Cashflow::DIRECTION_IN,
            'company_id' => $company->id,
            'amount' => 1000,

        ]);
        $init_cashflow_count = CashFlow::count();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 140.14,
            'vat_sum' => 10,
            'payment_term_days' => 0,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'advance_taxes' => $advance_taxes_data,
        ])->assertResponseStatus(200);

        $this->assertSame($init_invoice_payments, InvoicePayment::count());
        $invoice = $invoice->fresh();
        $invoice_payment = InvoicePayment::latest('id')->first();
        $this->assertSame(2000, $invoice_payment->amount);
        $this->assertSame(0, $invoice->payment_left);
        $this->assertSame($invoice->id, $invoice_payment->invoice_id);
        $this->assertSame($payment_method->id, $invoice_payment->payment_method_id);
        $this->assertFalse((bool) $invoice_payment->special_partial_payment);
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);

        $this->assertSame($init_cashflow_count + 2, CashFlow::count());
        $cashflow = CashFlow::latest('id')->where('direction', CashFlow::DIRECTION_IN)->first();
        $this->assertSame(2000, $cashflow->amount);
        $this->assertSame($company->id, $cashflow->company_id);
        $this->assertSame($invoice->id, $cashflow->invoice_id);
        $this->assertNull($cashflow->receipt_id);
        $this->assertSame(CashFlow::DIRECTION_IN, $cashflow->direction);
        $this->assertTrue((bool) $cashflow->cashless);
        $this->assertEmpty($cashflow->description);
        $this->assertSame($this->now->toDateString(), $cashflow->flow_date);

        $cashflow = CashFlow::latest('id')->where('direction', CashFlow::DIRECTION_OUT)->first();
        $this->assertSame(1000, $cashflow->amount);
        $this->assertSame($company->id, $cashflow->company_id);
        $this->assertSame($invoice->id, $cashflow->invoice_id);
        $this->assertNull($cashflow->receipt_id);
        $this->assertSame(CashFlow::DIRECTION_OUT, $cashflow->direction);
        $this->assertTrue((bool) $cashflow->cashless);
        $this->assertEmpty($cashflow->description);
        $this->assertSame($this->now->toDateString(), $cashflow->flow_date);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_final_advance()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $proforma = $this->createInvoice(InvoiceTypeStatus::PROFORMA);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id,
            'proforma_id' => $proforma->id,
            'payment_method_id' => $payment_method->id,
        ]);
        $advance_invoice = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'proforma_id' => $proforma->id,
        ]);
        $invoice->taxes()->delete();
        $invoice->finalAdvanceTaxes()->delete();

        $invoice_tax_reports = factory(InvoiceFinalAdvanceTaxReport::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);

        $invoice_tax_reports[0]->vatRate->name = 'final_vat_name';
        $invoice_tax_reports[0]->vatRate->save();
        $invoice_tax_reports[0]->update([
            'price_net' => 80,
            'price_gross' => 180,
        ]);
        $invoice_tax_reports[1]->vatRate->name = 'final_vat_name_2';
        $invoice_tax_reports[1]->vatRate->save();
        $invoice_tax_reports[1]->update([
            'price_net' => 280,
            'price_gross' => 2180,
        ]);
        $final_advance_tax = factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'price_net' => 100,
            'price_gross' => 200,
        ]);
        $array = [
            'Sprzedawca',
            'Faktura Zaliczkowa Końcowa',
            'nr sample_number',
            'Do proformy:',
            $invoice->proforma->number,
            'Data wystawienia:',
            $invoice->issue_date,
            'Cena',
            'Wartość',
            'VAT',
            number_format_output(100),
            'vat_name_a1',
            number_format_output(2000),
            'Razem',
            number_format_output(5600),
            'w tym',
            number_format_output(80),
            'vat_name',
            number_format_output(180),
            'Poprzednie zaliczki',
            'Rozliczenie zaliczki wg stawek',
            'Lp',
            $advance_invoice->number,
            $advance_invoice->issue_date,
            separators_format_output($advance_invoice->price_net),
            separators_format_output($advance_invoice->price_gross),
            separators_format_output($final_advance_tax->price_net),
            separators_format_output($final_advance_tax->price_gross - $final_advance_tax->price_net),
            separators_format_output($final_advance_tax->price_gross),
            'Zapłacono',
            number_format_output(5600),
            'Faktura Zaliczkowa Końcowa',

        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            $text_content = file_get_contents($text_file);
            $this->assertContainsOrdered($array, $text_content);
            File::deleteDirectory($directory);
        }
    }

    /**
     * @return array
     */
    protected function setIncomingDataForFinalAdvanceInvoice(): array
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $proforma = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
            'price_net' => 90,
            'price_gross' => 1000,
        ]);
        $proforma_items = factory(InvoiceItem::class, 4)->create([
            'invoice_id' => $proforma->id,
        ]);

        $proforma_items[0]->update([
            'price_net_sum' => 40,
            'price_gross_sum' => 50,
        ]);
        $advance_invoice = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'proforma_id' => $proforma->id,
            'price_net' => 40,
            'price_gross' => 50,
        ]);
        $advance_invoice_item = InvoiceItem::create(array_merge($proforma_items[0]->toArray(), [
                'invoice_id' => $advance_invoice->id,
                'proforma_item_id' => $proforma_items[0]->id,
                'price_net_sum' => 40,
                'price_gross_sum' => 50,
            ]));
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $init_invoice_count = Invoice::count();
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id;
        $incoming_data['proforma_id'] = $proforma->id;
        $incoming_data['payment_term_days'] = 0;
        Arr::set($incoming_data, 'items.0.proforma_item_id', $proforma_items[0]->id);
        Arr::set($incoming_data, 'items.1.proforma_item_id', $proforma_items[1]->id);
        Arr::set($incoming_data, 'items.2.proforma_item_id', $proforma_items[2]->id);
        Arr::set($incoming_data, 'items.3.proforma_item_id', $proforma_items[3]->id);
        $incoming_data['advance_taxes'] = [
            [
                'vat_rate_id' => factory(VatRate::class)->create()->id,
                'price_net' => 10,
                'price_gross' => 20,
            ],
        ];

        return [$company, $incoming_data, $init_invoice_count, $proforma, $proforma_items, $advance_invoice, $advance_invoice_item];
    }

    /**
     * @return array
     */
    protected function setIncomingDataForUpdateFinalAdvanceInvoice(): array
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $invoice_type_final_advance = InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE);
        $proforma = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $proforma_items = factory(InvoiceItem::class, 4)->create([
            'invoice_id' => $proforma->id,
        ]);
        $invoice->update([
            'invoice_type_id' => $invoice_type_final_advance->id,
            'proforma_id' => $proforma->id,
        ]);

        $invoice->nodeInvoices()->attach($proforma->id);

        Arr::set($items_data, '0.proforma_item_id', $proforma_items[0]->id);
        Arr::set($items_data, '1.proforma_item_id', $proforma_items[1]->id);
        Arr::set($items_data, '2.proforma_item_id', $proforma_items[2]->id);
        Arr::set($items_data, '3.proforma_item_id', $proforma_items[3]->id);

        $advance_taxes_data = [
            [
                'vat_rate_id' => factory(VatRate::class)->create()->id,
                'price_net' => 10,
                'price_gross' => 20,
            ],
        ];
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        factory(InvoiceFinalAdvanceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        return [$now, $company, $payment_method, $invoice, $items_data, $taxes_data, $invoice_type_final_advance, $proforma_items, $advance_taxes_data];
    }
}
