<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\XmlBuilder;

use App\Models\Other\SaleInvoice\Jpk\Attribute;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\XmlBuilder;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @test */
    public function it_creates_valid_xml_for_simple_element()
    {
        $xml_builder = new XmlBuilder();

        $element = new Element('abc:foo', 'bar');
        $element->addAttribute(new Attribute('one', 'two'));
        $element->addAttribute(new Attribute('three', 'four'));

        $xml = $xml_builder->create($element);

        $this->assertSame('<?xml version="1.0" encoding="utf-8"?>' . "\n" .
            '<abc:foo one="two" three="four">bar</abc:foo>' . "\n", $xml);
    }

    /** @test */
    public function it_creates_valid_xml_for_nested_structure()
    {
        $xml_builder = new XmlBuilder();

        $element = new Element('abc:foo', null);
        $element->addAttribute(new Attribute('one', 'two'));
        $element->addAttribute(new Attribute('three', 'four'));

        $child_element = new Element('first', 'child');
        $child_element2 = new Element('second', null);

        $grand_child = new Element('first', 'grandchild');
        $grand_child2 = new Element('second', 'sample grandchild');
        $grand_child2->addAttribute(new Attribute('attr', 15.23));

        $child_element2->addChild($grand_child);
        $child_element2->addChild($grand_child2);

        $element->addChild($child_element);
        $element->addChild($child_element2);

        $xml = $xml_builder->create($element);

        $this->assertSame('<?xml version="1.0" encoding="utf-8"?>' . "\n" .
            '<abc:foo one="two" three="four">' .
            '<first>child</first>' .
            '<second>' .
            '<first>grandchild</first>' .
            '<second attr="15.23">sample grandchild</second>' .
            '</second>' .
            '</abc:foo>' . "\n", $xml);
    }
}
