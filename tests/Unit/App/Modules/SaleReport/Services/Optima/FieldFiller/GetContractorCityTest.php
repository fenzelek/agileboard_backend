<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetContractorCityTest extends TestCase
{
    /** @test */
    public function it_returns_first_30_characters_from_city()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'main_address_city' => 'AAABBBCCC0DDDEEEFFF1GGGHHHIII2JJJKKKI',
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('AAABBBCCC0DDDEEEFFF1GGGHHHIII2', $field_filter->getContractorCity());
    }

    /** @test */
    public function it_returns_exact_city_if_city_contains_30_characters_or_less()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'main_address_city' => 'AAABBBCCC0DDDEEEFFF1GGGHHHIII2',
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame($invoice_contractor->main_address_city, $field_filter->getContractorCity());
    }

    /** @test */
    public function it_removes_not_allowed_characters_from_zcity()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'main_address_city' => '"Przykładowe,miasto",test"',
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('Przykładowe miasto  test', $field_filter->getContractorCity());
    }
}
