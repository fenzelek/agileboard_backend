<?php

namespace Tests\Unit\App\Models\Db\Invoice;

use App\Models\Db\Invoice;
use Tests\TestCase;

class GetPaymentDueTest extends TestCase
{
    /** @test */
    public function it_calculates_due_date_in_valid_way()
    {
        $invoice = new Invoice();
        $invoice->issue_date = '2017-11-30';
        $invoice->payment_term_days = 16;
        $this->assertSame('2017-12-16 00:00:00', $invoice->getPaymentDue()->toDateTimeString());
    }

    /** @test */
    public function it_returns_issue_date_when_no_payment_term_days_set()
    {
        $invoice = new Invoice();
        $invoice->issue_date = '2017-11-30';
        $invoice->payment_term_days = 0;
        $this->assertSame('2017-11-30 00:00:00', $invoice->getPaymentDue()->toDateTimeString());
    }
}
