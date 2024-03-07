<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetContractorZipCodeTest extends TestCase
{
    /** @test */
    public function it_returns_first_6_characters_from_zipcode()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'main_address_zip_code' => '1234567',
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('123456', $field_filter->getContractorZipCode());
    }

    /** @test */
    public function it_returns_exact_zip_code_if_6_character_only()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'main_address_zip_code' => '12-345',
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame($invoice_contractor->main_address_zip_code, $field_filter->getContractorZipCode());
    }

    /** @test */
    public function it_removes_not_allowed_characters_from_zipcode()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'main_address_zip_code' => '"12-345",',
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('12-345', $field_filter->getContractorZipCode());
    }
}
