<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController;

use App\Models\Db\CashFlow;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoicePayment;
use App\Models\Db\InvoiceReverseCharge;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\InvoiceType;
use App\Models\Db\Package;
use App\Models\Db\PaymentMethod;
use App\Models\Other\ModuleType;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceReverseChargeType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\InvoiceRegistry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\Feature\App\Modules\SaleInvoice\Helpers\FinancialEnvironment as FinancialEnvironmentTrait;
use Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload\FinancialEnvironment;
use File;

class AdvanceTest extends FinancialEnvironment
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
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'proforma_id',
            'payment_term_days',
        ]);
    }

    /** @test */
    public function store_validation_error_lack_proforma_item_id()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma) = $this->setIncomingDataForAdvanceInvoice();
        Arr::forget($incoming_data, 'items.0.proforma_item_id');

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.proforma_item_id',
        ]);
    }

    /** @test */
    public function store_disabled_advance_module_error()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, false);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_type_id',
        ]);
    }

    /** @test */
    public function store_return_error_lack_corrected_invoice()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->verifyErrorResponse(404);
    }

    /** @test */
    public function store_it_returns_validation_error_corrected_vat_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $invoice = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'company_id' => $company->id,
        ]);
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'corrected_invoice_id',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_corrected_advance_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $invoice = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION)->id,
            'company_id' => $company->id,
        ]);
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'corrected_invoice_id',
        ]);
    }

    /** @test */
    public function store_disabled_margin_module_error_try_issuing_advance_correction()
    {
        list($company, $invoice, $invoice_items, $incoming_data) = $this->setFinancialEnvironmentForCorrection();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, false);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_type_id',
        ]);
    }

    /** @test */
    public function store_always_add_payment_for_invoice()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma) = $this->setIncomingDataForAdvanceInvoice();

        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $incoming_data['payment_method_id'] = $payment_method->id;

        $init_payments_count = InvoicePayment::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_payments_count + 1, InvoicePayment::count());
        $invoice = Invoice::latest('id')->first();
        $invoice_payment = InvoicePayment::latest('id')->first();
        $this->assertSame(1111, $invoice_payment->amount);
        $this->assertSame(0, $invoice->payment_left);
        $this->assertSame($invoice->id, $invoice_payment->invoice_id);
        $this->assertSame($payment_method->id, $invoice_payment->payment_method_id);
        $this->assertFalse((bool) $invoice_payment->special_partial_payment);
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);

        $default_bank_account = $company->defaultBankAccount();
        $this->assertSame($default_bank_account->id, $invoice->bank_account_id);
        $this->assertSame($default_bank_account->bank_name, $invoice->invoiceCompany->bank_name);
        $this->assertSame($default_bank_account->number, $invoice->invoiceCompany->bank_account_number);
    }

    /** @test */
    public function store_always_add_cashflow_for_invoice()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma) = $this->setIncomingDataForAdvanceInvoice();

        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id;
        $incoming_data['payment_method_id'] = $payment_method->id;

        $init_payments_count = Cashflow::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_payments_count + 1, CashFlow::count());
        $invoice = Invoice::latest('id')->first();
        $cashflow = CashFlow::latest('id')->first();
        $this->assertSame(1111, $cashflow->amount);
        $this->assertSame($company->id, $cashflow->company_id);
        $this->assertSame($invoice->id, $cashflow->invoice_id);
        $this->assertNull($cashflow->receipt_id);
        $this->assertSame(CashFlow::DIRECTION_IN, $cashflow->direction);
        $this->assertTrue((bool) $cashflow->cashless);
        $this->assertEmpty($cashflow->description);
        $this->assertSame($incoming_data['issue_date'], $cashflow->flow_date);
    }

    /** @test */
    public function test_store_not_add_payment_and_cashflow_for_correction_invoice_if_bank_transfer()
    {
        $this->withoutExceptionHandling();
        list($company, $invoice, $invoice_items, $incoming_data) = $this->setFinancialEnvironmentForCorrection();
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $init_payments_count = InvoicePayment::count();
        $init_cashflow_count = CashFlow::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_payments_count, InvoicePayment::count());
        $this->assertSame($init_cashflow_count, CashFlow::count());
    }

    /** @test */
    public function store_invoice_number_queue_continue_vat_queue()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma, $proforma_items) = $this->setIncomingDataForAdvanceInvoice();

        $invoice_first_advance = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'order_number' => 1,
            'number' => '1/01/2017',
            'invoice_registry_id' => InvoiceRegistry::first()->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);
        $invoice_vat = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id,
            'order_number' => 2,
            'number' => '2/01/2017',
            'invoice_registry_id' => InvoiceRegistry::first()->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice_advance = Invoice::latest('id')->first();
        $this->assertSame(4, Invoice::count());
        $this->assertSame(3, $invoice_advance->order_number);
        $this->assertSame('ZAL/' . $this->registry->prefix . '/3/01/2017', $invoice_advance->number);
    }

    /** @test */
    public function store_correct_data_in_database()
    {
        list($company, $incoming_data, $init_invoice_count, $proforma, $proforma_items) = $this->setIncomingDataForAdvanceInvoice();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);
        $invoice_advance = Invoice::latest('id')->first();
        $this->assertSame($init_invoice_count + 1, Invoice::count());
        $this->assertSame(1, $invoice_advance->order_number);
        $this->assertSame('ZAL/' . $this->registry->prefix . '/1/01/2017', $invoice_advance->number);

        $invoice_expect_data = $this->invoiceExpectData();

        $this->assertSame($this->user->id, $invoice_advance->drawer_id);
        $this->assertSame($company->id, $invoice_advance->company_id);
        $this->assertNull($invoice_advance->corrected_invoice_margin_id);
        $this->assertNull($invoice_advance->correction_type);
        $this->assertEquals($incoming_data['sale_date'], $invoice_advance->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $invoice_advance->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $invoice_advance->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $invoice_advance->contractor_id);
        $this->assertSame($incoming_data['invoice_type_id'], $invoice_advance->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $invoice_advance->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice_advance->price_gross);
        $this->assertSame($invoice_expect_data['vat_sum'], $invoice_advance->vat_sum);
        $this->assertSame(0, $invoice_advance->payment_left);
        $this->assertSame($incoming_data['payment_method_id'], $invoice_advance->payment_method_id);
        $this->assertNull($invoice_advance->last_printed_at);
        $this->assertNull($invoice_advance->last_send_at);
        $this->assertNotNull($invoice_advance->created_at);
        $this->assertNull($invoice_advance->update_at);
        $this->assertEquals(Carbon::parse($incoming_data['issue_date'])->toDateTimeString(), $invoice_advance->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $invoice_advance->gross_counted);
        $this->assertNull($invoice_advance->invoice_margin_procedure_id);
        $this->assertNull($invoice_advance->invoice_reverse_charge_id);
        $this->assertSame($incoming_data['proforma_id'], $invoice_advance->proforma_id);

        $tax_report = InvoiceTaxReport::latest('id')->first();
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
        list($company, $incoming_data, $init_invoice_count, $proforma) = $this->setIncomingDataForAdvanceInvoice();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice = Invoice::latest('id')->first();
        $this->assertSame($incoming_data['proforma_id'], $invoice->nodeInvoices()->first()->id);
        $this->assertSame($incoming_data['proforma_id'], $invoice->proforma->id);
    }

    /** @test */
    public function store_correct_data_in_database_for_advance_correction()
    {
        list($company, $invoice, $invoice_items, $incoming_data) = $this->setFinancialEnvironmentForCorrection();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);

        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'invoice_reverse_charge_id' => InvoiceReverseCharge::findBySlug(InvoiceReverseChargeType::IN)->id,
            'number' => '10/10/2017',
            'order_number' => 10,
        ]);

        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION)->id;

        $initial_invoice_amount = Invoice::count();
        $initial_invoice_items_amount = InvoiceItem::count();
        $initial_invoice_taxes_amount = InvoiceTaxReport::count();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $correction_advance = Invoice::latest('id')->first();

        $invoice_expect_data = $this->invoiceExpectDataCorrectionInvoice($invoice_items);

        $this->assertSame($initial_invoice_amount + 1, Invoice::count());
        $this->assertSame('KOR/ZAL/' . $this->registry->prefix . '/1/01/2017', $correction_advance->number);
        $this->assertSame(1, $correction_advance->order_number);
        $this->assertSame($this->registry->id, $correction_advance->invoice_registry_id);
        $this->assertSame($this->user->id, $correction_advance->drawer_id);
        $this->assertSame($company->id, $correction_advance->company_id);
        $this->assertSame($incoming_data['corrected_invoice_id'], $correction_advance->corrected_invoice_id);
        $this->assertSame($incoming_data['correction_type'], $correction_advance->correction_type);
        $this->assertSame($invoice->delivery_address_id, $correction_advance->delivery_address_id);
        $this->assertEquals('2017-01-15', $correction_advance->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $correction_advance->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $correction_advance->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $correction_advance->contractor_id);
        $this->assertSame($incoming_data['invoice_type_id'], $correction_advance->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $correction_advance->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $correction_advance->price_gross);
        $this->assertSame($invoice_expect_data['vat_sum'], $correction_advance->vat_sum);
        $this->assertNull($correction_advance->payment_left);
        $this->assertSame($incoming_data['payment_method_id'], $correction_advance->payment_method_id);
        $this->assertNull($correction_advance->last_printed_at);
        $this->assertNull($correction_advance->last_send_at);
        $this->assertNotNull($correction_advance->created_at);
        $this->assertNull($correction_advance->update_at);
        $this->assertNull($correction_advance->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $correction_advance->gross_counted);
        $this->assertNull($correction_advance->invoice_margin_procedure_id);
        $this->assertNull($correction_advance->invoice_reverse_charge_id);
        $this->assertNull($correction_advance->proforma_id);

        $expect_added_invoice_items = 4;
        $invoice_items_expect = $this->invoiceExpectDataCorrectionInvoice($invoice_items)['items'];
        $invoice_items = InvoiceItem::latest('id')->take($expect_added_invoice_items)->get();

        $this->assertSame(
            $initial_invoice_items_amount + $expect_added_invoice_items,
            InvoiceItem::count()
        );
        $i = $expect_added_invoice_items;
        foreach ($invoice_items as $invoice_item) {
            $i -= 1;
            $this->assertSame($correction_advance->id, $invoice_item->invoice_id);
            $this->assertNull($invoice_item->custom_name);
            $this->assertSame($invoice_items_expect[$i]['price_net'], $invoice_item->price_net);
            $this->assertSame(
                $invoice_items_expect[$i]['price_net_sum'],
                $invoice_item->price_net_sum
            );
            $this->assertNull($invoice_item->price_gross);
            $this->assertSame(
                $invoice_items_expect[$i]['price_gross_sum'],
                $invoice_item->price_gross_sum
            );
            $this->assertSame($invoice_items_expect[$i]['vat_sum'], $invoice_item->vat_sum);
            $this->assertSame($invoice_items_expect[$i]['quantity'], $invoice_item->quantity);
            $this->assertTrue((bool) $invoice_item->is_correction);
            $this->assertSame(
                $invoice_items_expect[$i]['position_corrected_id'],
                $invoice_item->position_corrected_id
            );
            $this->assertSame($this->user->id, $invoice_item->creator_id);
            $this->assertFalse((bool) $invoice_item->editor_id);
            $this->assertNotNull($invoice_item->created_at);
            $this->assertNotNull($invoice_item->updated_at);
        }

        $this->assertSame($initial_invoice_taxes_amount + 1, InvoiceTaxReport::count());

        $tax_report = InvoiceTaxReport::latest('id')->first();
        $this->assertSame(-4040, $tax_report->price_net);
        $this->assertSame(-4040000, $tax_report->price_gross);
        $this->assertNotNull($tax_report->created_at);
        $this->assertNotNull($tax_report->updated_at);
    }

    /** @test */
    public function store_response_correct()
    {
        list($company, $incoming_data, $init_invoice_count) = $this->setIncomingDataForAdvanceInvoice();

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
    public function update_payment_term_days_validation_error()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION)->id,
        ]);
        $invoice->nodeInvoices()->attach($invoice->id);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'payment_term_days' => 10,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'payment_term_days',
            'id',
        ]);
    }

    /** @test */
    public function update_disabled_invoice_advance_module()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, false);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
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
        list($now, $company, $payment_method, $invoice, $items_data, $taxes_data, $invoice_type_advance) = $this->setIncomingDataForUpdateAdvanceInvoice();

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
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
        ])->assertResponseStatus(200);
    }

    /** @test */
    public function update_data_in_database()
    {
        list($now, $company, $payment_method, $invoice, $items_data, $taxes_data, $invoice_type_advance) = $this->setIncomingDataForUpdateAdvanceInvoice();

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
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
        ])->assertResponseStatus(200);
        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
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
        list($now, $company, $payment_method, $invoice, $items_data, $taxes_data, $invoice_type_advance, $proforma_items) = $this->setIncomingDataForUpdateAdvanceInvoice();

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
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
        ])->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), $invoice->items()->count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_items_fresh = $invoice_fresh->items;

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

        $this->assertSame($proforma_items[0]->id, $invoice->items()->skip(0)->first()->proforma_item_id);
        $this->assertSame($proforma_items[1]->id, $invoice->items()->skip(1)->first()->proforma_item_id);
        $this->assertSame($proforma_items[2]->id, $invoice->items()->skip(2)->first()->proforma_item_id);
        $this->assertSame($proforma_items[3]->id, $invoice->items()->skip(3)->first()->proforma_item_id);
    }

    /** @test */
    public function update_always_update_payment_cashflow_for_invoice()
    {
        list($now, $company, $payment_method, $invoice, $items_data, $taxes_data) = $this->setIncomingDataForUpdateAdvanceInvoice();

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
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
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
        ])->assertResponseStatus(200);

        $this->assertSame($init_invoice_payments, InvoicePayment::count());
        $invoice = $invoice->fresh();
        $invoice_payment = InvoicePayment::latest('id')->first();
        $this->assertSame(1414, $invoice_payment->amount);
        $this->assertSame(0, $invoice->payment_left);
        $this->assertSame($invoice->id, $invoice_payment->invoice_id);
        $this->assertSame($payment_method->id, $invoice_payment->payment_method_id);
        $this->assertFalse((bool) $invoice_payment->special_partial_payment);
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);

        $this->assertSame($init_cashflow_count + 2, CashFlow::count());
        $cashflow = CashFlow::latest('id')->where('direction', CashFlow::DIRECTION_IN)->first();
        $this->assertSame(1414, $cashflow->amount);
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
    public function pdf_print_advance()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'proforma_id' => $this->createInvoice(InvoiceTypeStatus::PROFORMA)->id,
            'payment_method_id' => $payment_method->id,
        ]);
        $array = [
            'Sprzedawca',
            'Faktura Zaliczkowa',
            'nr sample_number',
            'Do proformy:',
            $invoice->proforma->number,
            'Data wystawienia:',
            $invoice->issue_date,
            'Data otrzymania zaliczki',
            $invoice->sale_date,
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
            'Zapłacono',
            number_format_output(5600),
            'Faktura Zaliczkowa nr sample_number',
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
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_advance_correction()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setCorrectionInvoicePrintingEnvironment();

        $invoice_corrected = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'proforma_id' => $this->createInvoice(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $invoice->update([
            'correction_type' => InvoiceCorrectionType::QUANTITY,
            'corrected_invoice_id' => $invoice_corrected->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION)->id,
            'payment_method_id' => $payment_method->id,
            'number' => 'KOR/ZAL/1/1/2017',
            'price_gross' => -1111,
        ]);
        $array = [
            'Sprzedawca',
            'Faktura Zaliczkowa Korekta',
            $invoice->number,
            'Data wystawienia:',
            $invoice->issue_date,
            'Data korekty:',
            $invoice->sale_date,
            'Przed korektą',
            number_format_output(100),
            number_format_output(2000),
            'Po korekcie',
            'Cena',
            'Wartość',
            'VAT',
            number_format_output(100),
            'vat_name_a1',
            number_format_output(3600),
            'Razem',
            number_format_output(4300),
            'w tym',
            number_format_output(4300),
            'Razem do zwrotu',
            number_format_output(1111),
            'Faktura Zaliczkowa Korekta nr',
            $invoice->number,
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
    protected function setIncomingDataForAdvanceInvoice(): array
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $proforma = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $proforma_items = factory(InvoiceItem::class, 4)->create([
            'invoice_id' => $proforma->id,
        ]);
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $init_invoice_count = Invoice::count();
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id;
        $incoming_data['proforma_id'] = $proforma->id;
        $incoming_data['payment_term_days'] = 0;
        Arr::set($incoming_data, 'items.0.proforma_item_id', $proforma_items[0]->id);
        Arr::set($incoming_data, 'items.1.proforma_item_id', $proforma_items[1]->id);
        Arr::set($incoming_data, 'items.2.proforma_item_id', $proforma_items[2]->id);
        Arr::set($incoming_data, 'items.3.proforma_item_id', $proforma_items[3]->id);

        return [$company, $incoming_data, $init_invoice_count, $proforma, $proforma_items];
    }

    /**
     * @return array
     */
    protected function setIncomingDataForUpdateAdvanceInvoice(): array
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $invoice_type_advance = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE);
        $proforma = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $proforma_items = factory(InvoiceItem::class, 4)->create([
            'invoice_id' => $proforma->id,
        ]);
        $invoice->update([
            'invoice_type_id' => $invoice_type_advance->id,
            'proforma_id' => $proforma->id,
        ]);

        $invoice->nodeInvoices()->attach($proforma->id);

        Arr::set($items_data, '0.proforma_item_id', $proforma_items[0]->id);
        Arr::set($items_data, '1.proforma_item_id', $proforma_items[1]->id);
        Arr::set($items_data, '2.proforma_item_id', $proforma_items[2]->id);
        Arr::set($items_data, '3.proforma_item_id', $proforma_items[3]->id);

        return [$now, $company, $payment_method, $invoice, $items_data, $taxes_data, $invoice_type_advance, $proforma_items];
    }
}
