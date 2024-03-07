<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\Invoice;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;
use Mockery;

class GetPaidStatusTest extends TestCase
{
    /** @test */
    public function it_returns_paid_status_when_invoice_informs_it_is_paid()
    {
        /** @var Invoice $invoice */
        $invoice = Mockery::mock(Invoice::class);
        $invoice->shouldReceive('isPaid')->once()->andReturn(true);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(0, $field_filter->getPaidStatus());
    }

    /** @test */
    public function it_returns_not_paid_status_when_invoice_informs_it_is_not_paid()
    {
        /** @var Invoice $invoice */
        $invoice = Mockery::mock(Invoice::class);
        $invoice->shouldReceive('isPaid')->once()->andReturn(false);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(1, $field_filter->getPaidStatus());
    }
}
