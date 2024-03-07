<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceType;

use App\Models\Db\InvoiceType as InvoiceTypeModel;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceType;
use Tests\TestCase;

class CalculateTest extends TestCase
{
    /** @test */
    public function it_returns_vat_for_vat_invoice()
    {
        $this->verify(InvoiceTypeStatus::VAT, 'VAT');
    }

    /** @test */
    public function it_returns_korekta_for_correction_invoice()
    {
        $this->verify(InvoiceTypeStatus::CORRECTION, 'KOREKTA');
    }

    /** @test */
    public function it_returns_other_for_proforma_invoice()
    {
        $this->verify(InvoiceTypeStatus::PROFORMA, 'POZ');
    }

    /** @test */
    public function it_returns_vat_for_margin_invoice()
    {
        $this->verify(InvoiceTypeStatus::MARGIN, 'VAT', new InvoiceTypeModel(['slug' => InvoiceTypeStatus::VAT]));
    }

    /** @test */
    public function it_returns_korekta_for_margin_correction_invoice()
    {
        $this->verify(
            InvoiceTypeStatus::MARGIN_CORRECTION,
            'KOREKTA',
            new InvoiceTypeModel(['slug' => InvoiceTypeStatus::CORRECTION])
        );
    }

    /** @test */
    public function it_returns_vat_for_reverse_charge_invoice()
    {
        $this->verify(InvoiceTypeStatus::REVERSE_CHARGE, 'VAT', new InvoiceTypeModel(['slug' => InvoiceTypeStatus::VAT]));
    }

    /** @test */
    public function it_returns_korekta_for_reverse_charge_correction_invoice()
    {
        $this->verify(
            InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION,
            'KOREKTA',
            new InvoiceTypeModel(['slug' => InvoiceTypeStatus::CORRECTION])
        );
    }

    /** @test */
    public function it_returns_zal_for_advance_invoice()
    {
        $this->verify(InvoiceTypeStatus::ADVANCE, 'ZAL');
    }

    /** @test */
    public function it_returns_korekta_for_advance_correction_invoice()
    {
        $this->verify(InvoiceTypeStatus::ADVANCE_CORRECTION, 'KOREKTA');
    }

    /** @test */
    public function it_returns_vat_for_final_invoice()
    {
        $this->verify(InvoiceTypeStatus::FINAL_ADVANCE, 'ZAL', new InvoiceTypeModel(['slug' => InvoiceTypeStatus::ADVANCE]));
    }

    protected function verify($initial_type, $expected_type, InvoiceTypeModel $parent_invoice_type = null)
    {
        $invoice_type_model = new InvoiceTypeModel(['slug' => $initial_type]);

        if ($parent_invoice_type) {
            $invoice_type_model->parent_type_id = 'whatever';
            $invoice_type_model->setRelation('parentType', $parent_invoice_type);
        }

        $invoice_type = new InvoiceType();

        $this->assertSame($expected_type, $invoice_type->calculate($invoice_type_model));
    }
}
