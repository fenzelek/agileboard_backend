<?php

namespace Tests\Unit\App\Models\Other\SaleInvoice\Jpk\Attribute;

use App\Models\Other\SaleInvoice\Jpk\Attribute;
use Tests\TestCase;

class ToArrayTest extends TestCase
{
    /** @test */
    public function it_returns_valid_representation_of_object_when_value_is_string()
    {
        $fancy_value = 'TEST_testABC23123;"X45123XEruqiu';

        $attribute = new Attribute('test', $fancy_value);
        $this->assertSame([
            'name' => 'test',
            'value' => $fancy_value,
        ], $attribute->toArray());
    }

    /** @test */
    public function it_returns_exactly_same_value_as_set_for_numeric_value()
    {
        $numeric_value = 123.312;

        $attribute = new Attribute('test', $numeric_value);
        $this->assertSame([
            'name' => 'test',
            'value' => $numeric_value,
        ], $attribute->toArray());
    }

    /** @test */
    public function it_returns_exactly_same_value_as_set_for_null_value()
    {
        $attribute = new Attribute('test', null);
        $this->assertSame([
            'name' => 'test',
            'value' => null,
        ], $attribute->toArray());
    }
}
