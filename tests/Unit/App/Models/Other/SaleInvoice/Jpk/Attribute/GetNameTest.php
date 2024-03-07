<?php

namespace Tests\Unit\App\Models\Other\SaleInvoice\Jpk\Attribute;

use App\Models\Other\SaleInvoice\Jpk\Attribute;
use Tests\TestCase;

class GetNameTest extends TestCase
{
    /** @test */
    public function it_returns_exactly_same_name_as_set()
    {
        $fancy_name = 'TEST_testABC23123;"X45123XEruqiu';

        $attribute = new Attribute($fancy_name, null);
        $this->assertSame($fancy_name, $attribute->getName());
    }
}
