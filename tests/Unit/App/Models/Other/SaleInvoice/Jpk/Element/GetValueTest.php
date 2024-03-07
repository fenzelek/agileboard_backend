<?php

namespace Tests\Unit\App\Models\Other\SaleInvoice\Jpk\Element;

use App\Models\Other\SaleInvoice\Jpk\Element;
use Tests\TestCase;

class GetValueTest extends TestCase
{
    /** @test */
    public function it_returns_exactly_same_value_as_set_for_string_value()
    {
        $fancy_value = 'TEST_testABC23123;"X45123XEruqiu';

        $element = new Element('test', $fancy_value);
        $this->assertSame($fancy_value, $element->getValue());
    }

    /** @test */
    public function it_returns_exactly_same_value_as_set_for_numeric_value()
    {
        $numeric_value = 123.312;

        $element = new Element('test', $numeric_value);
        $this->assertSame($numeric_value, $element->getValue());
    }

    /** @test */
    public function it_returns_exactly_same_value_as_set_for_null_value()
    {
        $element = new Element('test', null);
        $this->assertNull($element->getValue());
    }

    /** @test */
    public function it_sets_value_to_null_when_none_given()
    {
        $element = new Element('test');
        $this->assertNull($element->getValue());
    }
}
