<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\NoVat;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\InvoiceReverseCharge;
use App\Models\Db\InvoiceType;
use App\Models\Other\ModuleType;
use App\Models\Other\InvoiceMarginProcedureType;
use App\Models\Other\InvoiceReverseChargeType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\SaleInvoice\Payers\NoVat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload\FinancialEnvironment;
use Carbon\Carbon;
use Tests\Feature\App\Modules\SaleInvoice\Helpers\FinancialEnvironment as FinancialEnvironmentTrait;

class NoVatCorrectionTest extends FinancialEnvironment
{
    use DatabaseTransactions, FinancialEnvironmentTrait;

    private $now;
    private $registry;
    private $company;
    private $delivery_address;
    private $incoming_data;
    private $np_vat_rate;
    private $invoice_items;
    private $invoice;

    public function setUp():void
    {
        parent::setUp();
        $this->registry = factory(InvoiceRegistry::class)->create();
        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);

        list($this->company, $this->invoice, $this->invoice_items, $this->incoming_data) = $this->setFinancialEnvironmentForCorrection();

        $this->customizeAmountSettingForNoVatPayer();

        $this->invoice->update([
            'gross_counted' => NoVat::COUNT_TYPE,
        ]);
    }

    /** @test */
    public function store_database_saving_no_vat_correction()
    {
        $this->post('invoices?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(201);

        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $this->assertNoVatProperties($invoice_type);
    }

    /** @test */
    public function store_it_correct_margin_correction_saved_properties()
    {
        $this->setAppSettings($this->company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN_CORRECTION);
        $this->incoming_data['invoice_type_id'] = $invoice_type->id;
        $this->incoming_data['invoice_margin_procedure_id'] = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id;
        $this->invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
        ]);

        $this->post('invoices?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(201);

        $this->assertNoVatProperties($invoice_type);
    }

    /** @test */
    public function store_it_correct_reverse_charge_correction_saved_properties()
    {
        $this->setAppSettings($this->company, ModuleType::INVOICES_REVERSE_CHARGE_ENABLED, true);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION);
        $this->incoming_data['invoice_type_id'] = $invoice_type->id;
        $this->incoming_data['invoice_reverse_charge_id'] = InvoiceReverseCharge::findBySlug(InvoiceReverseChargeType::OUT)->id;
        $this->invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE)->id,
        ]);
        $this->post('invoices?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(201);

        $this->assertNoVatProperties($invoice_type);
    }

    /** @test */
    public function store_it_correct_advance_correction_saved_properties()
    {
        $this->setAppSettings($this->company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION);
        $this->incoming_data['invoice_type_id'] = $invoice_type->id;

        $proforma = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $proforma_items = factory(InvoiceItem::class, 4)->create([
            'invoice_id' => $proforma->id,
        ]);

        $this->invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
        ]);

        $this->incoming_data['proforma_id'] = $proforma->id;
        $this->incoming_data['payment_term_days'] = 0;
        array_set($this->incoming_data, 'items.0.proforma_item_id', $proforma_items[0]->id);
        array_set($this->incoming_data, 'items.1.proforma_item_id', $proforma_items[1]->id);
        array_set($this->incoming_data, 'items.2.proforma_item_id', $proforma_items[2]->id);
        array_set($this->incoming_data, 'items.3.proforma_item_id', $proforma_items[3]->id);

        $this->post('invoices?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(201);

        $this->assertNoVatProperties($invoice_type);
    }
}
