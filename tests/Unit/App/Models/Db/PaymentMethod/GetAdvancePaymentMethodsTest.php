<?php

namespace Tests\Unit\App\Models\Db\PaymentMethod;

use App\Models\Db\PaymentMethod;
use App\Models\Other\PaymentMethodType;
use Tests\TestCase;

class GetAdvancePaymentMethodsTest extends TestCase
{
    /** @test */
    public function it_returns_valid_elements()
    {
        $this->assertSame([
            PaymentMethodType::CASH,
            PaymentMethodType::DEBIT_CARD,
            PaymentMethodType::CASH_CARD,
            PaymentMethodType::PAYU,
        ], PaymentMethod::getAdvancedPaymentMethods());
    }
}
