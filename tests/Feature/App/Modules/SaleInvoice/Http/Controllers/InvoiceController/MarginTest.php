<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceFormat;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\InvoiceType;
use App\Models\Db\VatRate;
use App\Models\Other\ModuleType;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceMarginProcedureType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\VatRateType;
use App\Models\Db\InvoiceRegistry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\BrowserKitTestCase;
use Tests\Feature\App\Modules\SaleInvoice\Helpers\FinancialEnvironment;

class MarginTest extends BrowserKitTestCase
{
    use DatabaseTransactions, FinancialEnvironment;

    public function setUp():void
    {
        parent::setUp();
        $this->registry = factory(InvoiceRegistry::class)->create();
    }

    /** @test */
    public function store_validation_error_invoice_margin_procedure()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_margin_procedure_id',
        ]);
    }

    /** @test */
    public function store_validation_error_fake_invoice_margin_procedure()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id;
        $invoice_margin_procedure = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART);
        $invoice_margin_procedure_id = $invoice_margin_procedure->id;
        $invoice_margin_procedure->delete();
        $incoming_data['invoice_margin_procedure_id'] = $invoice_margin_procedure_id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_margin_procedure_id',
        ]);
    }

    /** @test */
    public function store_validation_error_vat_rate()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'vat_sum',
            'items.0.vat_rate_id',
            'items.0.vat_sum',
            'taxes.0.vat_rate_id',
        ]);
    }

    /** @test */
    public function store_disabled_margin_module_error()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, false);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id;
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
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN_CORRECTION)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(404);
    }

    /** @test */
    public function store_it_returns_validation_error_corrected_vat_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'company_id' => $company->id,
        ]);
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN_CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'corrected_invoice_id',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_corrected_margin_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
            'company_id' => $company->id,
        ]);
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'corrected_invoice_id',
        ]);
    }

    /** @test */
    public function store_validation_error_correction_type_for_margin_correction()
    {
        list($company, $invoice, $invoice_items, $incoming_data) = $this->setFinancialEnvironmentForCorrection();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN_CORRECTION)->id;
        $incoming_data['correction_type'] = InvoiceCorrectionType::TAX;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'vat_sum',
            'items.0.vat_rate_id',
            'items.0.vat_sum',
            'taxes.0.vat_rate_id',
            'correction_type',
            'invoice_margin_procedure_id',
        ]);
    }

    /** @test */
    public function store_disabled_margin_module_error_try_issuing_margin_correction()
    {
        list($company, $invoice, $invoice_items, $incoming_data) = $this->setFinancialEnvironmentForCorrection();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, false);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN_CORRECTION)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_type_id',
        ]);
    }

    /** @test */
    public function store_invoice_number_queue_continue_vat_queue()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice_vat = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
            'order_number' => 1,
            'number' => '1/01/2017',
            'invoice_registry_id' => InvoiceRegistry::first()->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);
        $invoice_first_margin = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'order_number' => 2,
            'number' => '2/01/2017',
            'invoice_registry_id' => InvoiceRegistry::first()->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);

        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id;
        $incoming_data['invoice_margin_procedure_id'] = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id;
        $incoming_data['vat_sum'] = 0;
        $vat_rate = $this->app->make(VatRate::class);
        $vat_rate_id = $vat_rate->findByName(VatRateType::NP)->id;
        foreach ($incoming_data['items'] as $key => $item) {
            Arr::set($incoming_data, 'items.' . $key . '.vat_sum', 0);
            Arr::set($incoming_data, 'items.' . $key . '.vat_rate_id', $vat_rate_id);
        }
        Arr::set($incoming_data, 'taxes.0.vat_rate_id', $vat_rate_id);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice_margin = Invoice::latest('id')->first();
        $this->assertSame(3, Invoice::count());
        $this->assertSame(3, $invoice_margin->order_number);
        $this->assertSame($this->registry->prefix . '/3/01/2017', $invoice_margin->number);
    }

    /** @test */
    public function store_invoice_number_queue_not_continue_correction_queue()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice_vat = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'order_number' => 1,
            'number' => 'KOR/1/01/2017',
            'invoice_registry_id' => InvoiceRegistry::first()->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);
        $invoice_first_margin = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'order_number' => 1,
            'number' => '1/01/2017',
            'invoice_registry_id' => InvoiceRegistry::first()->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);

        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id;
        $incoming_data['invoice_margin_procedure_id'] = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id;
        $incoming_data['vat_sum'] = 0;
        $vat_rate = $this->app->make(VatRate::class);
        $vat_rate_id = $vat_rate->findByName(VatRateType::NP)->id;
        foreach ($incoming_data['items'] as $key => $item) {
            Arr::set($incoming_data, 'items.' . $key . '.vat_sum', 0);
            Arr::set($incoming_data, 'items.' . $key . '.vat_rate_id', $vat_rate_id);
        }
        Arr::set($incoming_data, 'taxes.0.vat_rate_id', $vat_rate_id);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice_margin = Invoice::latest('id')->first();
        $this->assertSame(3, Invoice::count());
        $this->assertSame(2, $invoice_margin->order_number);
        $this->assertSame($this->registry->prefix . '/2/01/2017', $invoice_margin->number);
    }

    /** @test */
    public function store_invoice_vat_number_queue_continue_vat_queue()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $invoice_vat = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'order_number' => 1,
            'number' => '1/01/2017',
            'invoice_registry_id' => InvoiceRegistry::first()->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);
        $invoice_first_margin = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
            'order_number' => 2,
            'number' => '2/01/2017',
            'invoice_registry_id' => InvoiceRegistry::first()->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice_margin = Invoice::latest('id')->first();
        $this->assertSame(3, Invoice::count());
        $this->assertSame(3, $invoice_margin->order_number);
        $this->assertSame($this->registry->prefix . '/3/01/2017', $invoice_margin->number);
    }

    /** @test */
    public function store_invoice_margin_correction_number_queue_continue_correction_queue()
    {
        list($company, $invoice, $invoice_items, $incoming_data) = $this->setFinancialEnvironmentForCorrection();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice_vat = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'order_number' => 1,
            'number' => '1/01/2017',
            'invoice_registry_id' => $this->registry->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);
        $invoice_first_margin = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN_CORRECTION)->id,
            'order_number' => 2,
            'number' => '2/01/2017',
            'invoice_registry_id' => $this->registry->id,
            'order_number_date' => Carbon::parse('2017-01-01'),
            'company_id' => $company->id,
        ]);

        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id;
        $incoming_data['correction_type'] = InvoiceCorrectionType::QUANTITY;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice_margin = Invoice::latest('id')->first();
        $this->assertSame(3, $invoice_margin->order_number);
        $this->assertSame('KOR/' . $this->registry->prefix . '/3/01/2017', $invoice_margin->number);
    }

    /** @test */
    public function store_correct_data_in_database()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id;
        $incoming_data['invoice_margin_procedure_id'] = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id;
        $incoming_data['vat_sum'] = 0;
        $vat_rate_model = $this->app->make(VatRate::class);
        $vat_rate = $vat_rate_model->findByName(VatRateType::NP);
        $vat_rate_id = $vat_rate->id;
        foreach ($incoming_data['items'] as $key => $item) {
            Arr::set($incoming_data, 'items.' . $key . '.vat_sum', 0);
            Arr::set($incoming_data, 'items.' . $key . '.vat_rate_id', $vat_rate_id);
        }
        Arr::set($incoming_data, 'taxes.0.vat_rate_id', $vat_rate_id);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice_margin = Invoice::latest('id')->first();
        $this->assertSame(1, Invoice::count());
        $this->assertSame(1, $invoice_margin->order_number);
        $this->assertSame($this->registry->prefix . '/1/01/2017', $invoice_margin->number);

        $invoice_expect_data = $this->invoiceExpectData();

        $this->assertSame($this->user->id, $invoice_margin->drawer_id);
        $this->assertSame($company->id, $invoice_margin->company_id);
        $this->assertNull($invoice_margin->corrected_invoice_margin_id);
        $this->assertNull($invoice_margin->correction_type);
        $this->assertEquals($incoming_data['sale_date'], $invoice_margin->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $invoice_margin->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $invoice_margin->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $invoice_margin->contractor_id);
        $this->assertSame($incoming_data['invoice_type_id'], $invoice_margin->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $invoice_margin->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice_margin->price_gross);
        $this->assertSame(0, $invoice_margin->vat_sum);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice_margin->payment_left);
        $this->assertSame($incoming_data['payment_method_id'], $invoice_margin->payment_method_id);
        $this->assertNull($invoice_margin->last_printed_at);
        $this->assertNull($invoice_margin->last_send_at);
        $this->assertNotNull($invoice_margin->created_at);
        $this->assertNull($invoice_margin->update_at);
        $this->assertNull($invoice_margin->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $invoice_margin->gross_counted);
        $this->assertSame($incoming_data['invoice_margin_procedure_id'], $invoice_margin->invoice_margin_procedure_id);

        $tax_report = InvoiceTaxReport::latest('id')->first();
        $invoice_items_expect = $this->invoiceExpectData_gross_count()['taxes'];
        $this->assertSame($invoice_items_expect[0]['price_net'], $tax_report->price_net);
        $this->assertSame($invoice_items_expect[0]['price_gross'], $tax_report->price_gross);
        $this->assertSame($vat_rate->id, $tax_report->vat_rate_id);
        $this->assertNotNull($tax_report->created_at);
        $this->assertNotNull($tax_report->updated_at);

        $items = InvoiceItem::latest('id')->skip(3)->first();
        $invoice_items_expect = $this->invoiceExpectData()['items'];
        $this->assertSame($invoice_items_expect[0]['price_net_sum'], $items->price_net_sum);
        $this->assertSame($invoice_items_expect[0]['price_gross_sum'], $items->price_gross_sum);
        $this->assertSame(0, $items->vat_sum);
        $items = InvoiceItem::latest('id')->skip(2)->first();
        $invoice_items_expect = $this->invoiceExpectData()['items'];
        $this->assertSame($invoice_items_expect[1]['price_net_sum'], $items->price_net_sum);
        $this->assertSame($invoice_items_expect[1]['price_gross_sum'], $items->price_gross_sum);
        $this->assertSame(0, $items->vat_sum);
        $items = InvoiceItem::latest('id')->skip(1)->first();
        $invoice_items_expect = $this->invoiceExpectData()['items'];
        $this->assertSame($invoice_items_expect[2]['price_net_sum'], $items->price_net_sum);
        $this->assertSame($invoice_items_expect[2]['price_gross_sum'], $items->price_gross_sum);
        $this->assertSame(0, $items->vat_sum);
        $items = InvoiceItem::latest('id')->first();
        $invoice_items_expect = $this->invoiceExpectData()['items'];
        $this->assertSame($invoice_items_expect[3]['price_net_sum'], $items->price_net_sum);
        $this->assertSame($invoice_items_expect[3]['price_gross_sum'], $items->price_gross_sum);
        $this->assertSame(0, $items->vat_sum);
    }

    /** @test */
    public function store_registry_with_start_number_wont_trigger_different_numbering()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id;
        $incoming_data['invoice_margin_procedure_id'] = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id;
        $incoming_data['vat_sum'] = 0;
        $vat_rate_model = $this->app->make(VatRate::class);
        $vat_rate = $vat_rate_model->findByName(VatRateType::NP);
        $vat_rate_id = $vat_rate->id;
        foreach ($incoming_data['items'] as $key => $item) {
            Arr::set($incoming_data, 'items.' . $key . '.vat_sum', 0);
            Arr::set($incoming_data, 'items.' . $key . '.vat_rate_id', $vat_rate_id);
        }
        Arr::set($incoming_data, 'taxes.0.vat_rate_id', $vat_rate_id);

        $year_format = InvoiceFormat::findByFormatStrict(InvoiceFormat::YEARLY_FORMAT);
        $registry = factory(InvoiceRegistry::class)->create([
            'start_number' => 123,
            'company_id' => $company->id,
            'prefix' => '',
            'invoice_format_id' => $year_format->id,
        ]);
        $incoming_data['invoice_registry_id'] = $registry->id;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice_margin = Invoice::latest('id')->first();
        $this->assertEquals('1/2017', $invoice_margin->number);
        $this->assertEquals(123, $registry->fresh()->start_number);
    }

    /** @test */
    public function store_correct_data_in_database_for_margin_correction()
    {
        list($company, $invoice, $invoice_items, $incoming_data) = $this->setFinancialEnvironmentForCorrection();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);

        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
            'invoice_margin_procedure_id' => InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::TOUR_OPERATOR)->id,
            'number' => '10/10/2017',
            'order_number' => 10,
        ]);

        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN_CORRECTION)->id;
        $incoming_data['invoice_margin_procedure_id'] = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id;
        $incoming_data['vat_sum'] = 0;
        $vat_rate_model = $this->app->make(VatRate::class);
        $vat_rate = $vat_rate_model->findByName(VatRateType::NP);
        $vat_rate_id = $vat_rate->id;
        foreach ($incoming_data['items'] as $key => $item) {
            Arr::set($incoming_data, 'items.' . $key . '.vat_sum', 0);
            Arr::set($incoming_data, 'items.' . $key . '.vat_rate_id', $vat_rate_id);
        }
        Arr::set($incoming_data, 'taxes.0.vat_rate_id', $vat_rate_id);
        Arr::set($incoming_data, 'taxes.0.price_net', -40.40);
        Arr::set($incoming_data, 'taxes.0.price_gross', -40.40);
        $initial_invoice_amount = Invoice::count();
        $initial_invoice_items_amount = InvoiceItem::count();
        $initial_invoice_taxes_amount = InvoiceTaxReport::count();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $correction_margin = Invoice::latest('id')->first();

        $invoice_expect_data = $this->invoiceExpectDataCorrectionInvoice($invoice_items);

        $this->assertSame($initial_invoice_amount + 1, Invoice::count());
        $this->assertSame('KOR/' . $this->registry->prefix . '/1/01/2017', $correction_margin->number);
        $this->assertSame(1, $correction_margin->order_number);
        $this->assertSame($this->registry->id, $correction_margin->invoice_registry_id);
        $this->assertSame($this->user->id, $correction_margin->drawer_id);
        $this->assertSame($company->id, $correction_margin->company_id);
        $this->assertSame($incoming_data['corrected_invoice_id'], $correction_margin->corrected_invoice_id);
        $this->assertSame($incoming_data['correction_type'], $correction_margin->correction_type);
        $this->assertSame($invoice->delivery_address_id, $correction_margin->delivery_address_id);
        $this->assertEquals('2017-01-15', $correction_margin->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $correction_margin->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $correction_margin->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $correction_margin->contractor_id);
        $this->assertSame($incoming_data['invoice_type_id'], $correction_margin->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $correction_margin->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $correction_margin->price_gross);
        $this->assertSame(0, $correction_margin->vat_sum);
        $this->assertNull($correction_margin->payment_left);
        $this->assertSame($incoming_data['payment_method_id'], $correction_margin->payment_method_id);
        $this->assertNull($correction_margin->last_printed_at);
        $this->assertNull($correction_margin->last_send_at);
        $this->assertNotNull($correction_margin->created_at);
        $this->assertNull($correction_margin->update_at);
        $this->assertNull($correction_margin->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $correction_margin->gross_counted);
        $this->assertSame($incoming_data['invoice_margin_procedure_id'], $correction_margin->invoice_margin_procedure_id);

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
            $this->assertSame($correction_margin->id, $invoice_item->invoice_id);
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
            $this->assertEquals($vat_rate->rate, $invoice_item->vat_rate);
            $this->assertSame($vat_rate->id, $invoice_item->vat_rate_id);
            $this->assertSame(0, $invoice_item->vat_sum);
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
        $this->assertSame(-4040, $tax_report->price_gross);
        $this->assertSame($vat_rate->id, $tax_report->vat_rate_id);
        $this->assertNotNull($tax_report->created_at);
        $this->assertNotNull($tax_report->updated_at);
    }

    /** @test */
    public function store_response_correct()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);

        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id;
        $incoming_data['invoice_margin_procedure_id'] = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id;
        $incoming_data['vat_sum'] = 0;
        $vat_rate_model = $this->app->make(VatRate::class);
        $vat_rate = $vat_rate_model->findByName(VatRateType::NP);
        $vat_rate_id = $vat_rate->id;
        foreach ($incoming_data['items'] as $key => $item) {
            Arr::set($incoming_data, 'items.' . $key . '.vat_sum', 0);
            Arr::set($incoming_data, 'items.' . $key . '.vat_rate_id', $vat_rate_id);
        }
        Arr::set($incoming_data, 'taxes.0.vat_rate_id', $vat_rate_id);

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
        $this->assertSame(0, $json_data['vat_sum']);
        $this->assertSame(11.11, $json_data['payment_left']);
        $this->assertSame($invoice->payment_method_id, $json_data['payment_method_id']);
        $this->assertNull($json_data['paid_at']);
        $this->assertEquals($invoice->gross_counted, $json_data['gross_counted']);
        $this->assertNull($json_data['last_printed_at']);
        $this->assertNull($json_data['last_send_at']);
        $this->assertEquals($invoice->created_at, $json_data['created_at']);
        $this->assertEquals($invoice->updated_at, $json_data['updated_at']);
        $this->assertSame($incoming_data['invoice_margin_procedure_id'], $json_data['invoice_margin_procedure_id']);
    }

    /** @test */
    public function update_vat_sums_and_margin_procedure_validation_error()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
        ]);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_method_id' => $payment_method->id,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_margin_procedure_id',
            'vat_sum',
            'items.0.vat_rate_id',
            'items.0.vat_sum',
            'taxes.0.vat_rate_id',
        ]);
    }

    /** @test */
    public function update_vat_sums_and_margin_procedure_and_type_validation_error_for_correction()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
        ]);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'invoice_margin_procedure_id' => 'no_valid_margin_procedures',
            'vat_sum' => 15.78,
            'payment_method_id' => $payment_method->id,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_margin_procedure_id',
            'vat_sum',
            'items.0.vat_rate_id',
            'items.0.vat_sum',
            'taxes.0.vat_rate_id',
        ]);
    }

    /** @test */
    public function update_disabled_invoice_margin_module()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, false);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
        ]);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 0,
            'payment_left' => 7,
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
    public function update_data_in_database()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice_type_margin = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN);
        $invoice->update([
            'invoice_type_id' => $invoice_type_margin->id,
        ]);
        $invoice_margin_procedure = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::TOUR_OPERATOR);
        $vat_rate = $this->app->make(VatRate::class);
        $vat_rate_id = $vat_rate->findByName(VatRateType::NP)->id;
        Arr::set($items_data, '0.vat_rate_id', $vat_rate_id);
        Arr::set($items_data, '1.vat_rate_id', $vat_rate_id);
        Arr::set($items_data, '2.vat_rate_id', $vat_rate_id);
        Arr::set($items_data, '3.vat_rate_id', $vat_rate_id);
        Arr::set($taxes_data, '0.vat_rate_id', $vat_rate_id);
        Arr::set($items_data, '0.vat_sum', 0);
        Arr::set($items_data, '1.vat_sum', 0);
        Arr::set($items_data, '2.vat_sum', 0);
        Arr::set($items_data, '3.vat_sum', 0);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 0,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'invoice_margin_procedure_id' => $invoice_margin_procedure->id,
        ])->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), InvoiceItem::count());

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
        $this->assertNull($invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice_type_margin->id, $invoice_fresh->invoice_type_id);
        $this->assertSame(1234, $invoice_fresh->price_net);
        $this->assertSame(1481, $invoice_fresh->price_gross);
        $this->assertSame(0, $invoice_fresh->vat_sum);
        $this->assertSame(1481, $invoice_fresh->payment_left);
        $this->assertSame(5, $invoice_fresh->payment_term_days);
        $this->assertSame($payment_method->id, $invoice_fresh->payment_method_id);
        $this->assertSame(1, $invoice_fresh->gross_counted);
        $this->assertSame($invoice_margin_procedure->id, $invoice_fresh->invoice_margin_procedure_id);

        $this->assertSame(0, $invoice_items_fresh[0]->vat_sum);
        $this->assertSame($vat_rate_id, $invoice_items_fresh[0]->vat_rate_id);
        $this->assertSame(0, $invoice_items_fresh[1]->vat_sum);
        $this->assertSame($vat_rate_id, $invoice_items_fresh[1]->vat_rate_id);
        $this->assertSame(0, $invoice_items_fresh[2]->vat_sum);
        $this->assertSame($vat_rate_id, $invoice_items_fresh[2]->vat_rate_id);
        $this->assertSame(0, $invoice_items_fresh[3]->vat_sum);
        $this->assertSame($vat_rate_id, $invoice_items_fresh[3]->vat_rate_id);
    }

    /** @test */
    public function update_check_response_json()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice_type_margin = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN);
        $invoice->update([
            'invoice_type_id' => $invoice_type_margin->id,
        ]);
        $invoice_margin_procedure = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART);
        $vat_rate = $this->app->make(VatRate::class);
        $vat_rate_id = $vat_rate->findByName(VatRateType::NP)->id;
        Arr::set($items_data, '0.vat_rate_id', $vat_rate_id);
        Arr::set($items_data, '1.vat_rate_id', $vat_rate_id);
        Arr::set($items_data, '2.vat_rate_id', $vat_rate_id);
        Arr::set($items_data, '3.vat_rate_id', $vat_rate_id);
        Arr::set($taxes_data, '0.vat_rate_id', $vat_rate_id);
        Arr::set($items_data, '0.vat_sum', 0);
        Arr::set($items_data, '1.vat_sum', 0);
        Arr::set($items_data, '2.vat_sum', 0);
        Arr::set($items_data, '3.vat_sum', 0);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 0,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'invoice_margin_procedure_id' => $invoice_margin_procedure->id,
        ])->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), InvoiceItem::count());

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
        $this->assertNull($response['paid_at']);
        $this->assertSame('2017-02-02', $response['issue_date']);
        $this->assertSame($invoice_type_margin->id, $response['invoice_type_id']);
        $this->assertSame(12.34, $response['price_net']);
        $this->assertSame(14.81, $response['price_gross']);
        $this->assertSame(0, $response['vat_sum']);
        $this->assertSame(14.81, $response['payment_left']);
        $this->assertSame(5, $response['payment_term_days']);
        $this->assertSame($payment_method->id, $response['payment_method_id']);
        $this->assertSame(1, $response['gross_counted']);
        $this->assertSame($invoice_margin_procedure->id, $response['invoice_margin_procedure_id']);
    }
}
