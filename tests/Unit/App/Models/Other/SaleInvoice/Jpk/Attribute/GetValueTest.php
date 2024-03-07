<?php

namespace Tests\Unit\App\Models\Other\SaleInvoice\Jpk\Attribute;

use App\Models\Other\SaleInvoice\Jpk\Attribute;
use Tests\TestCase;

class GetValueTest extends TestCase
{
    /** @test */
    public function it_returns_exactly_same_value_as_set_for_string_value()
    {
        $fancy_value = 'TEST_testABC23123;"X45123XEruqiu';

        $attribute = new Attribute('test', $fancy_value);
        $this->assertSame($fancy_value, $attribute->getValue());
    }

    /** @test */
    public function it_returns_exactly_same_value_as_set_for_numeric_value()
    {
        $numeric_value = 123.312;

        $attribute = new Attribute('test', $numeric_value);
        $this->assertSame($numeric_value, $attribute->getValue());
    }

    /** @test */
    public function it_returns_exactly_same_value_as_set_for_null_value()
    {
        $attribute = new Attribute('test', null);
        $this->assertNull($attribute->getValue());
    }
}
