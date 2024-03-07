<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\Invoice;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetDocumentNumberTest extends TestCase
{
    /** @test */
    public function it_returns_first_15_characters_from_invoice_number()
    {
        $invoice = new Invoice([
            'number' => 'AAABBBCCC0DDDEEEFFF1GGGHHHIII2JJJKKKI',
        ]);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('AAABBBCCC0DDDEE', $field_filter->getDocumentNumber());
    }

    /** @test */
    public function it_removes_not_allowed_characters_from_invoice_number()
    {
        $invoice = new Invoice([
            'number' => '"AAA,BBBCCC0DDDEEEFFF1GGGHHHIII2JJJKKKI',
        ]);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame('AAA BBBCCC0DDDE', $field_filter->getDocumentNumber());
    }
}
