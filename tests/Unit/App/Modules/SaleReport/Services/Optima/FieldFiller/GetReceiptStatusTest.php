<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\Invoice;
use App\Models\Db\Receipt;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GetReceiptStatusTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_non_receipt_based_status_for_standard_invoice()
    {
        $invoice = new Invoice();

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(0, $field_filter->getReceiptStatus());
    }

    /** @test */
    public function it_returns_corrected_document_when_corrected_invoice_is_set()
    {
        $invoice = factory(Invoice::class)->create();
        $receipt = factory(Receipt::class)->create();
        $invoice->receipts()->attach($receipt);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(1, $field_filter->getReceiptStatus());
    }
}
