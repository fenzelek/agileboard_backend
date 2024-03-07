<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetContractorVatInTest extends TestCase
{
    /** @test */
    public function it_returns_first_15_characters_from_vatin_when_empty_prefix()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789ABCEFGH',
            'country_vatin_prefix_id' => null,
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('123456789ABCEFG', $field_filter->getContractorVatin());
    }

    /** @test */
    public function it_returns_first_15_characters_from_vatin_merged_with_prefix()
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

        $this->assertSame('TEST' . '123456789AB', $field_filter->getContractorVatin());
    }

    /** @test */
    public function it_removes_not_allowed_characters_from_vatin()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '",123456789ABCEFGH',
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('123456789ABCEFG', $field_filter->getContractorVatin());
    }
}
