<?php

namespace Tests\Unit\App\Models\Db\PaymentMethod;

use App\Models\Db\PaymentMethod;
use App\Models\Other\PaymentMethodType;
use Tests\TestCase;

class IsInAdvanceTest extends TestCase
{
    /** @test */
    public function it_returns_false_for_bank_transfer()
    {
        $this->verifyForSlug(PaymentMethodType::BANK_TRANSFER, false);
    }

    /** @test */
    public function it_returns_true_for_cash()
    {
        $this->verifyForSlug(PaymentMethodType::CASH, true);
    }

    /** @test */
    public function it_returns_true_for_bank_debit_card()
    {
        $this->verifyForSlug(PaymentMethodType::DEBIT_CARD, true);
    }

    /** @test */
    public function it_returns_false_for_prepaid()
    {
        $this->verifyForSlug(PaymentMethodType::PREPAID, false);
    }

    /** @test */
    public function it_returns_false_for_other()
    {
        $this->verifyForSlug(PaymentMethodType::OTHER, false);
    }

    /** @test */
    public function it_returns_true_for_cash_card()
    {
        $this->verifyForSlug(PaymentMethodType::CASH_CARD, true);
    }

    /** @test */
    public function it_returns_false_for_cash_on_delivery()
    {
        $this->verifyForSlug(PaymentMethodType::CASH_ON_DELIVERY, false);
    }

    /** @test */
    public function it_returns_false_for_payu()
    {
        $this->verifyForSlug(PaymentMethodType::PAYU, true);
    }

    protected function verifyForSlug($slug, $expected_result)
    {
        $payment_method = new PaymentMethod();
        $payment_method->slug = $slug;

        $this->assertSame($expected_result, $payment_method->isInAdvance());
    }
}
