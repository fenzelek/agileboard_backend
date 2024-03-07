<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetContractorAddressTest extends TestCase
{
    /** @test */
    public function it_returns_only_40_characters_from_address()
    {
        $invoice = new Invoice();

        $first_40_characters = 'AAABBBCCC0DDDEEEFFF1GGGHHHIII2JJJKKKLLL3';
        $second_40_characters = 'MMMNNNOOO4PPPRRRSSS5TTTUUUVVV6WWWXXXYYY7';

        $invoice_contractor = new InvoiceContractor([
            'main_address_street' => $first_40_characters . $second_40_characters .
                'ZZZABCDEF8GHIJKLMNO9',
            'main_address_number' => '16A',
        ]);
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame($first_40_characters, $field_filter->getContractorAddress());
    }

    /** @test */
    public function it_returns_address_and_number_when_both_are_short_enough()
    {
        $invoice = new Invoice();
        $invoice_contractor = new InvoiceContractor(
            [
                'main_address_street' => 'AAABBBCCC0DDDEEEFFF1GGGHHHIII2JJJKKK',
                'main_address_number' => '16A',
            ]
        );
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame($invoice_contractor->main_address_street . ' ' .
            $invoice_contractor->main_address_number, $field_filter->getContractorAddress());
    }

    /** @test */
    public function it_returns_address_and_cut_number_when_total_is_too_long()
    {
        $invoice = new Invoice();
        $invoice_contractor = new InvoiceContractor(
            [
                'main_address_street' => 'AAABBBCCC0DDDEEEFFF1GGGHHHIII2JJJKKKI',
                'main_address_number' => '16A',
            ]
        );
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(
            $invoice_contractor->main_address_street . ' ' . '16',
            $field_filter->getContractorAddress()
        );
    }

    /** @test */
    public function it_removes_not_allowed_characters_in_address_and_number()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor(
            [
                'main_address_street' => '"Very sample,street"',
                'main_address_number' => '"16,A"',
            ]
        );
        $invoice->setRelation('invoiceContractor', $invoice_contractor);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('Very sample street 16 A', $field_filter->getContractorAddress());
    }
}
