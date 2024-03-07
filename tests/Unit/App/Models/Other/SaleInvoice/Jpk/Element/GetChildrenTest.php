<?php

namespace Tests\Unit\App\Models\Other\SaleInvoice\Jpk\Element;

use App\Models\Other\SaleInvoice\Jpk\Element;
use Tests\TestCase;
use TypeError;

class GetChildrenTest extends TestCase
{
    /** @test */
    public function it_throws_exception_when_non_element_value_passed_and_returns_empty_attributes()
    {
        $element = new Element('test');
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Argument 1 passed to App\Models\Other\SaleInvoice\Jpk\Element::addChild() must be an instance of App\Models\Other\SaleInvoice\Jpk\Element, string given');
        $this->assertSame([], $element->getChildren());
        $element->addChild('test');

        $this->assertSame([], $element->getChildren());
    }

    /** @test */
    public function it_returns_the_same_element_when_one_element_was_set()
    {
        $child_element = new Element('fancy_name', 'fancey_value_231AEruxu3$!@#');

        $element = new Element('test');
        $this->assertCount(0, $element->getChildren());
        $element->addChild($child_element);

        $this->assertCount(1, $element->getChildren());
        $this->assertSame($child_element, $element->getChildren()[0]);
    }

    /** @test */
    public function it_returns_same_elements_when_two_elements_were_set()
    {
        $child_element = new Element('fancy_name', 'fancey_value_231AEruxu3$!@#');
        $child_element_2 = new Element('other', 3123);

        $element = new Element('test');
        $this->assertCount(0, $element->getChildren());
        $element->addChild($child_element);
        $element->addChild($child_element_2);

        $this->assertCount(2, $element->getChildren());
        $this->assertSame($child_element, $element->getChildren()[0]);
        $this->assertSame($child_element_2, $element->getChildren()[1]);
    }
}
