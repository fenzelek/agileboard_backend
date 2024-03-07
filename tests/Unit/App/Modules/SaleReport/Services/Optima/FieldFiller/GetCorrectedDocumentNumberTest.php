<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\Invoice;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetCorrectedDocumentNumberTest extends TestCase
{
    /** @test */
    public function it_returns_corrected_invoice_number_when_it_is_not_empty()
    {
        $invoice = new Invoice();

        $corrected_invoice = new Invoice([
            'number' => 'SAMPLE_NUMBER',
        ]);
        $invoice->setRelation('correctedInvoice', $corrected_invoice);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame($corrected_invoice->number, $field_filter->getCorrectedDocumentNumber());
    }

    /** @test */
    public function it_returns_15_characters_only_from_corrected_invoice_number()
    {
        $invoice = new Invoice();

        $corrected_invoice = new Invoice([
            'number' => 'SAMPLE_NUMBER123456',
        ]);
        $invoice->setRelation('correctedInvoice', $corrected_invoice);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('SAMPLE_NUMBER12', $field_filter->getCorrectedDocumentNumber());
    }

    /** @test */
    public function it_removes_not_allowed_characters_from_corrected_invoice_number()
    {
        $invoice = new Invoice();

        $corrected_invoice = new Invoice([
            'number' => '"AAA,BBBCCC0DDDEEEFFF1GGGHHHIII2JJJKKKI',
        ]);
        $invoice->setRelation('correctedInvoice', $corrected_invoice);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('AAA BBBCCC0DDDE', $field_filter->getCorrectedDocumentNumber());
    }

    /** @test */
    public function it_returns_empty_string_when_no_corrected_invoice_set()
    {
        $invoice = new Invoice();

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('', $field_filter->getCorrectedDocumentNumber());
    }
}
