<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\Invoice;
use App\Models\Db\PaymentMethod;
use App\Models\Other\PaymentMethodType;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Exception;
use Tests\TestCase;

class GetPaymentMethodStatusTest extends TestCase
{
    /** @test */
    public function it_returns_transfer_for_bank_transfer()
    {
        $this->verifyForTypeAndExpectedType(PaymentMethodType::BANK_TRANSFER, 3);
    }

    /** @test */
    public function it_returns_cash_for_cash()
    {
        $this->verifyForTypeAndExpectedType(PaymentMethodType::CASH, 1);
    }

    /** @test */
    public function it_returns_card_for_debit_card()
    {
        $this->verifyForTypeAndExpectedType(PaymentMethodType::DEBIT_CARD, 6);
    }

    /** @test */
    public function it_returns_prepaid_for_prepaid()
    {
        $this->verifyForTypeAndExpectedType(PaymentMethodType::PREPAID, 8);
    }

    /** @test */
    public function it_returns_other_for_other()
    {
        $this->verifyForTypeAndExpectedType(PaymentMethodType::OTHER, 5);
    }

    /** @test */
    public function it_returns_other_for_cash_card()
    {
        $this->verifyForTypeAndExpectedType(PaymentMethodType::CASH_CARD, 5);
    }

    /** @test */
    public function it_returns_other_for_cash_on_delivery()
    {
        $this->verifyForTypeAndExpectedType(PaymentMethodType::CASH_ON_DELIVERY, 5);
    }

    /** @test */
    public function it_returns_other_for_payu()
    {
        $this->verifyForTypeAndExpectedType(PaymentMethodType::PAYU, 5);
    }

    /** @test */
    public function it_throws_exception_for_not_recognized_slug()
    {
        $invoice = new Invoice();

        $payment_method = new PaymentMethod(['slug' => 'INVALID']);

        $invoice->setRelation('paymentMethod', $payment_method);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown payment type');

        $field_filter->getPaymentMethod();
    }

    protected function verifyForTypeAndExpectedType($slug, $expected_type)
    {
        $invoice = new Invoice();

        $payment_method = new PaymentMethod(['slug' => $slug]);

        $invoice->setRelation('paymentMethod', $payment_method);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame($expected_type, $field_filter->getPaymentMethod());
    }
}
