<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\Invoice;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetDocumentTypeTest extends TestCase
{
    /** @test */
    public function it_returns_normal_document_when_its_standard_invoice()
    {
        $invoice = new Invoice();

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(0, $field_filter->getDocumentType());
    }

    /** @test */
    public function it_returns_corrected_document_when_corrected_invoice_is_set()
    {
        $invoice = new Invoice();

        $corrected_invoice = new Invoice([
            'number' => 'SAMPLE_NUMBER',
        ]);
        $invoice->setRelation('correctedInvoice', $corrected_invoice);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(1, $field_filter->getDocumentType());
    }
}
