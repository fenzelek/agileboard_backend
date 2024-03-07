<?php

namespace Tests\Unit\App\Models\Db\Invoice;

use App\Models\Db\Invoice;
use Tests\TestCase;

class GetRawPaidAmountTest extends TestCase
{
    /** @test */
    public function it_returns_valid_value_for_invoice_with_positive_price_gross()
    {
        $invoice = new Invoice();
        $invoice->price_gross = 231331;
        $invoice->payment_left = 14823;
        $this->assertSame(216508, $invoice->getRawPaidAmount());
    }

    /** @test */
    public function it_returns_valid_when_price_gross_is_negative_and_payment_left_is_positive()
    {
        $invoice = new Invoice();
        $invoice->price_gross = -231331;
        $invoice->payment_left = 14823;
        $this->assertSame(-216508, $invoice->getRawPaidAmount());
    }

    /** @test */
    public function it_returns_valid_when_price_gross_is_negative_and_payment_left_is_negative()
    {
        $invoice = new Invoice();
        $invoice->price_gross = -231331;
        $invoice->payment_left = -14823;
        $this->assertSame(-216508, $invoice->getRawPaidAmount());
    }
}
