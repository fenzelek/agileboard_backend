<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetContractorTypeTest extends TestCase
{
    /** @test */
    public function it_returns_company_when_vatin_filled()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789ABCEFGH',
            'country_vatin_prefix_id' => null,
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(0, $field_filter->getContractorType());
    }

    /** @test */
    public function it_return_company_when_vatin_prefix_filled()
    {
        $invoice = new Invoice();

        $vatin_prefix = new CountryVatinPrefix(['key' => 'TEST']);

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789ABCEFGH',
        ]);
        $invoice_contractor->setRelation('vatinPrefix', $vatin_prefix);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(0, $field_filter->getContractorType());
    }

    /** @test */
    public function it_returns_person_when_vatin_empty()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '',
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(1, $field_filter->getContractorType());
    }
}
