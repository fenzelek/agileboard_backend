<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\NoVat;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\InvoiceReverseCharge;
use App\Models\Db\InvoiceType;
use App\Models\Db\VatRate;
use App\Models\Other\ModuleType;
use App\Models\Other\InvoiceMarginProcedureType;
use App\Models\Other\InvoiceReverseChargeType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\SaleInvoice\Payers\NoVat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload\FinancialEnvironment;
use Carbon\Carbon;
use Tests\Feature\App\Modules\SaleInvoice\Helpers\FinancialEnvironment as FinancialEnvironmentTrait;

class UpdateTest extends FinancialEnvironment
{
    use DatabaseTransactions, FinancialEnvironmentTrait;

    private $now;
    private $registry;
    private $company;
    private $delivery_address;
    private $incoming_data;
    private $np_vat_rate;
    private $taxes_data;
    private $items_data;
    private $payment_method;
    private $invoice;

    public function setUp():void
    {
        parent::setUp();
        $this->registry = factory(InvoiceRegistry::class)->create();
        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);

        list($this->company, $this->payment_method, $this->contractor, $this->invoice, $this->items_data, $this->taxes_data) = $this->setFinancialEnvironmentForUpdate();

        $this->incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.11,
            'vat_sum' => 10,
            'payment_term_days' => 0,
            'payment_method_id' => $this->payment_method->id,
            'items' => $this->items_data,
            'taxes' => $this->taxes_data,
        ];

        $this->incoming_data['invoice_type_id'] =
            InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $this->customizeAmountSettingForNoVatPayer();
        $this->invoice->update([
            'gross_counted' => NoVat::COUNT_TYPE,
        ]);
    }

    /** @test */
    public function update_no_equal_net_and_gross_validation_error()
    {
        $no_valid_price_net = $this->incoming_data['price_gross'] + 1;
        array_set($this->incoming_data, 'price_net', $no_valid_price_net);
        $no_valid_price_net_item = array_get($this->incoming_data, 'items.0.price_gross') + 1;
        array_set($this->incoming_data, 'items.0.price_net', $no_valid_price_net_item);
        $no_valid_item_price_net_sum = array_get($this->incoming_data, 'items.0.price_gross_sum') + 1;
        array_set($this->incoming_data, 'items.0.price_net_sum', $no_valid_item_price_net_sum);
        $no_valid_taxes_price_net = array_get($this->incoming_data, 'taxes.0.price_gross') + 1;
        array_set($this->incoming_data, 'taxes.0.price_net', $no_valid_taxes_price_net);

        $this->put('invoices/' . $this->invoice->id . '?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'price_net',
            'items.0.price_net',
            'items.0.price_net_sum',
            'taxes.0.price_net',
        ]);
    }

    /** @test */
    public function update_no_equal_net_and_gross_in_advance_taxes_validation_error()
    {
        $this->setAppSettings($this->company, ModuleType::INVOICES_ADVANCE_ENABLED, true);

        $invoice_type_advance = InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE);

        $this->invoice->update([
            'invoice_type_id' => $invoice_type_advance->id,
        ]);

        $this->incoming_data['advance_taxes'] = [
            [
                'vat_rate_id' => $this->np_vat_rate->id,
                'price_net' => 1,
                'price_gross' => 100,
            ],
        ];

        $this->put('invoices/' . $this->invoice->id . '?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'advance_taxes.0.price_net',
        ]);
    }

    /** @test */
    public function update_not_np_vat_rate_validation_error()
    {
        $no_valid_vat_rate = factory(VatRate::class)->create();

        array_set($this->incoming_data, 'items.0.vat_rate_id', $no_valid_vat_rate->id);
        array_set($this->incoming_data, 'taxes.0.vat_rate_id', $no_valid_vat_rate->id);

        $this->put('invoices/' . $this->invoice->id . '?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'items.0.vat_rate_id',
            'taxes.0.vat_rate_id',
        ]);
    }

    /** @test */
    public function update_not_equal_zero_vat_sum_validation_error()
    {
        array_set($this->incoming_data, 'vat_sum', '1');
        array_set($this->incoming_data, 'items.0.vat_sum', 1);

        $this->put('invoices/' . $this->invoice->id . '?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'vat_sum',
            'items.0.vat_sum',
        ]);
    }

    /** @test */
    public function update_bad_setting_gross_counted_validation_error()
    {
        array_set($this->incoming_data, 'gross_counted', '0');

        $this->put('invoices/' . $this->invoice->id . '?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'gross_counted',
        ]);
    }

    /** @test */
    public function update_database_saving_no_vat()
    {
        $this->put('invoices/' . $this->invoice->id . '?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(200);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::VAT);
        $this->assertNoVatProperties($invoice_type);
    }

    /** @test */
    public function update_it_correct_margin_saved_properties()
    {
        $this->setAppSettings($this->company, ModuleType::INVOICES_MARGIN_ENABLED, true);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN);
        $this->incoming_data['invoice_type_id'] = $invoice_type->id;
        $this->incoming_data['invoice_margin_procedure_id'] = InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id;

        $this->invoice->update([
            'invoice_type_id' => $invoice_type->id,
        ]);

        $this->put('invoices/' . $this->invoice->id . '?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(200);

        $this->assertNoVatProperties($invoice_type);
    }

    /** @test */
    public function update_it_correct_reverse_charge_saved_properties()
    {
        $this->setAppSettings($this->company, ModuleType::INVOICES_REVERSE_CHARGE_ENABLED, true);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE);
        $this->incoming_data['invoice_type_id'] = $invoice_type->id;
        $this->incoming_data['invoice_reverse_charge_id'] = InvoiceReverseCharge::findBySlug(InvoiceReverseChargeType::OUT)->id;

        $this->invoice->update([
            'invoice_type_id' => $invoice_type->id,
        ]);

        $this->put('invoices/' . $this->invoice->id . '?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(200);

        $this->assertNoVatProperties($invoice_type);
    }

    /** @test */
    public function update_it_correct_advance_saved_properties()
    {
        $this->setAppSettings($this->company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE);
        $this->incoming_data['invoice_type_id'] = $invoice_type->id;

        $proforma = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $proforma_items = factory(InvoiceItem::class, 4)->create([
            'invoice_id' => $proforma->id,
        ]);

        $this->invoice->update([
            'invoice_type_id' => $invoice_type->id,
            'proforma_id' => $proforma->id,
        ]);

        $this->invoice->nodeInvoices()->attach($proforma->id);

        $this->incoming_data['proforma_id'] = $proforma->id;
        $this->incoming_data['payment_term_days'] = 0;
        array_set($this->incoming_data, 'items.0.proforma_item_id', $proforma_items[0]->id);
        array_set($this->incoming_data, 'items.1.proforma_item_id', $proforma_items[1]->id);
        array_set($this->incoming_data, 'items.2.proforma_item_id', $proforma_items[2]->id);
        array_set($this->incoming_data, 'items.3.proforma_item_id', $proforma_items[3]->id);

        $this->put('invoices/' . $this->invoice->id . '?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(200);

        $this->assertNoVatProperties($invoice_type);
    }

    /** @test */
    public function update_it_correct_final_advance_saved_properties()
    {
        $this->setAppSettings($this->company, ModuleType::INVOICES_ADVANCE_ENABLED, true);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE);
        $this->incoming_data['invoice_type_id'] = $invoice_type->id;

        $proforma = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $proforma_items = factory(InvoiceItem::class, 4)->create([
            'invoice_id' => $proforma->id,
        ]);

        $this->invoice->update([
            'invoice_type_id' => $invoice_type->id,
            'proforma_id' => $proforma->id,
        ]);

        $this->invoice->nodeInvoices()->attach($proforma->id);

        $this->incoming_data['proforma_id'] = $proforma->id;
        array_set($this->incoming_data, 'items.0.proforma_item_id', $proforma_items[0]->id);
        array_set($this->incoming_data, 'items.1.proforma_item_id', $proforma_items[1]->id);
        array_set($this->incoming_data, 'items.2.proforma_item_id', $proforma_items[2]->id);
        array_set($this->incoming_data, 'items.3.proforma_item_id', $proforma_items[3]->id);

        $this->incoming_data['advance_taxes'] = [
            [
                'vat_rate_id' => $this->np_vat_rate->id,
                'price_net' => 100,
                'price_gross' => 100,
            ],
        ];

        $this->put('invoices/' . $this->invoice->id . '?selected_company_id=' . $this->company->id, $this->incoming_data)
            ->assertResponseStatus(200);

        $this->assertNoVatProperties($invoice_type);

        $invoice = Invoice::whereHas('invoiceType', function ($query) use ($invoice_type) {
            $query->where('id', $invoice_type->id);
        })->latest('id')->first();

        $tax = $invoice->taxes()->first();
        $this->assertEquals($this->np_vat_rate->id, $tax->vatRate->id);
        $this->assertEquals(10000, $tax->price_net);
        $this->assertEquals(10000, $tax->price_gross);

        collect($invoice->finalAdvanceTaxes)->reduce(function ($next, $item) {
            $this->assertEquals($this->np_vat_rate->id, $item->vatRate->id);
            $this->assertEquals($this->incoming_data['taxes'][$next]['price_gross'], denormalize_price($item->price_net));
            $this->assertEquals($this->incoming_data['taxes'][$next++]['price_gross'], denormalize_price($item->price_gross));

            return $next;
        }, 0);
    }
}
