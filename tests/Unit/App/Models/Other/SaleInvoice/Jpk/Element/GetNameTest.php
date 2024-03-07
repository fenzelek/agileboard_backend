<?php

namespace Tests\Unit\App\Models\Other\SaleInvoice\Jpk\Element;

use App\Models\Other\SaleInvoice\Jpk\Element;
use Tests\TestCase;

class GetNameTest extends TestCase
{
    /** @test */
    public function it_returns_exactly_same_name_as_set()
    {
        $fancy_name = 'TEST_testABC23123;"X45123XEruqiu';

        $element = new Element($fancy_name, null);
        $this->assertSame($fancy_name, $element->getName());
    }
}
