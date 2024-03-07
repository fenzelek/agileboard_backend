<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetContractorNameTest extends TestCase
{
    /** @test */
    public function it_returns_long_name_split_into_2_parts()
    {
        $invoice = new Invoice();

        $first_40_characters = 'AAABBBCCC0DDDEEEFFF1GGGHHHIII2JJJKKKLLL3';
        $second_40_characters = 'MMMNNNOOO4PPPRRRSSS5TTTUUUVVV6WWWXXXYYY7';

        $invoice_contractor = new InvoiceContractor([
            'name' => $first_40_characters . $second_40_characters . 'ZZZABCDEF8GHIJKLMNO9',
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(
            [$first_40_characters, $second_40_characters],
            $field_filter->getContractorName()
        );
    }

    /** @test */
    public function it_returns_empty_second_chunk_when_name_is_shorter_than_40_characters()
    {
        $invoice = new Invoice();
        $invoice_contractor = new InvoiceContractor(['name' => 'ABC']);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame([$invoice_contractor->name, ''], $field_filter->getContractorName());
    }

    /** @test */
    public function it_removes_not_allowed_characters_before_split_into_chunks()
    {
        $invoice = new Invoice();

        $first_40_characters = '"AAABBBCCC0DDDEEEFFF1GGGHHHIII2JJJKKKLL,';
        $second_40_characters = 'L3M",MMNNNOOO4PPPRRRSSS5TTTUUUVVV6WWWY7,';

        $invoice_contractor = new InvoiceContractor([
            'name' => $first_40_characters . $second_40_characters,
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(
            [
            'AAABBBCCC0DDDEEEFFF1GGGHHHIII2JJJKKKLL L',
            '3M  MMNNNOOO4PPPRRRSSS5TTTUUUVVV6WWWY7',
        ],
            $field_filter->getContractorName()
        );
    }
}
