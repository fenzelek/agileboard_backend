<?php

namespace Tests\Unit\App\Models\Other\SaleInvoice\Jpk\Element;

use App\Models\Other\SaleInvoice\Jpk\Attribute;
use App\Models\Other\SaleInvoice\Jpk\Element;
use Tests\TestCase;
use TypeError;

class GetAttributesTest extends TestCase
{
    /** @test */
    public function it_throws_exception_when_non_attribute_value_passed_and_returns_empty_attributes()
    {
        $element = new Element('test');
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Argument 1 passed to App\Models\Other\SaleInvoice\Jpk\Element::addAttribute() must be an instance of App\Models\Other\SaleInvoice\Jpk\Attribute, string given');
        $this->assertSame([], $element->getAttributes());
        $element->addAttribute('test');

        $this->assertSame([], $element->getAttributes());
    }

    /** @test */
    public function it_returns_the_same_element_when_one_attribute_was_set()
    {
        $attribute = new Attribute('fancy_name', 'fancey_value_231AEruxu3$!@#');

        $element = new Element('test');
        $this->assertCount(0, $element->getAttributes());
        $element->addAttribute($attribute);

        $this->assertCount(1, $element->getAttributes());
        $this->assertSame($attribute, $element->getAttributes()[0]);
    }

    /** @test */
    public function it_returns_same_elements_when_two_attributes_were_set()
    {
        $attribute = new Attribute('fancy_name', 'fancey_value_231AEruxu3$!@#');
        $attribute2 = new Attribute('other', 3123);

        $element = new Element('test');
        $this->assertCount(0, $element->getAttributes());
        $element->addAttribute($attribute);
        $element->addAttribute($attribute2);

        $this->assertCount(2, $element->getAttributes());
        $this->assertSame($attribute, $element->getAttributes()[0]);
        $this->assertSame($attribute2, $element->getAttributes()[1]);
    }
}
