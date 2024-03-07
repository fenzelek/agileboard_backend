<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetExportTypeTest extends TestCase
{
    /** @test */
    public function it_returns_country_transaction_when_contractor_doesnt_have_vatin()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
            'country_vatin_prefix_id' => null,
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(0, $field_filter->getExportType());
    }

    /** @test */
    public function it_returns_country_transaction_when_contractor_has_polish_vatin()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
            'country_vatin_prefix_id' => CountryVatinPrefix::where('key', 'PL')->value('id'),
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(0, $field_filter->getExportType());
    }

    /** @test */
    public function it_returns_country_transaction_when_contractor_has_polish_vatin_and_correction_invoice()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
            'country_vatin_prefix_id' => CountryVatinPrefix::where('key', 'PL')->value('id'),
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $corrected_invoice = new Invoice([
            'number' => 'SAMPLE_NUMBER123456',
        ]);
        $invoice->setRelation('correctedInvoice', $corrected_invoice);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(0, $field_filter->getExportType());
    }

    /** @test */
    public function it_returns_ue_transaction_when_contractor_has_ue_vatin()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
            'country_vatin_prefix_id' => CountryVatinPrefix::where('key', 'HU')->value('id'),
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $corrected_invoice = new Invoice([
            'number' => 'SAMPLE_NUMBER123456',
        ]);
        $invoice->setRelation('correctedInvoice', $corrected_invoice);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(3, $field_filter->getExportType());
    }

    /** @test */
    public function it_returns_ue_transaction_when_contractor_has_ue_vatin_and_correction_transaction()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
            'country_vatin_prefix_id' => CountryVatinPrefix::where('key', 'HU')->value('id'),
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $corrected_invoice = new Invoice([
            'number' => 'SAMPLE_NUMBER123456',
        ]);
        $invoice->setRelation('correctedInvoice', $corrected_invoice);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(3, $field_filter->getExportType());
    }

    /** @test */
    public function it_returns_export_transaction_when_contractor_has_not_ue_vatin()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
            'country_vatin_prefix_id' => CountryVatinPrefix::where('key', 'AF')->value('id'),
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(1, $field_filter->getExportType());
    }

    /** @test */
    public function it_returns_export_transaction_refund_when_contractor_has_not_ue_vatin_and_correction_transaction()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
            'country_vatin_prefix_id' => CountryVatinPrefix::where('key', 'AF')->value('id'),
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $corrected_invoice = new Invoice([
            'number' => 'SAMPLE_NUMBER123456',
        ]);
        $invoice->setRelation('correctedInvoice', $corrected_invoice);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(2, $field_filter->getExportType());
    }
}
