<?php

namespace Tests\Unit\App\Models\Other\SaleInvoice\Jpk\Element;

use App\Models\Other\SaleInvoice\Jpk\Attribute;
use App\Models\Other\SaleInvoice\Jpk\Element;
use Tests\TestCase;

class ToArrayTest extends TestCase
{
    /** @test */
    public function it_returns_valid_representation_of_object_when_value_is_string()
    {
        $fancy_value = 'TEST_testABC23123;"X45123XEruqiu';

        $element = new Element('test', $fancy_value);
        $this->assertSame([
            'name' => 'test',
            'value' => $fancy_value,
            'attributes' => [],
            'children' => [],
        ], $element->toArray());
    }

    /** @test */
    public function it_returns_valid_representation_of_object_when_value_as_set_for_numeric_value()
    {
        $numeric_value = 123.312;

        $element = new Element('test', $numeric_value);
        $this->assertSame([
            'name' => 'test',
            'value' => $numeric_value,
            'attributes' => [],
            'children' => [],
        ], $element->toArray());
    }

    /** @test */
    public function it_returns_valid_representation_of_object_when_value_as_set_for_null_value()
    {
        $element = new Element('test', null);
        $this->assertSame([
            'name' => 'test',
            'value' => null,
            'attributes' => [],
            'children' => [],
        ], $element->toArray());
    }

    /** @test */
    public function it_returns_valid_representation_of_object_when_attributes_were_set()
    {
        $attribute = new Attribute('fancy_name', 'fancey_value_231AEruxu3$!@#');
        $attribute2 = new Attribute('other', 3123);

        $element = new Element('test', null);
        $element->addAttribute($attribute);
        $element->addAttribute($attribute2);

        $this->assertSame([
            'name' => 'test',
            'value' => null,
            'attributes' => [
                [
                    'name' => 'fancy_name',
                    'value' => 'fancey_value_231AEruxu3$!@#',
                ],
                [
                    'name' => 'other',
                    'value' => 3123,
                ],
            ],
            'children' => [],
        ], $element->toArray());
    }

    /** @test */
    public function it_returns_valid_representation_of_object_when_children_were_set()
    {
        $child_element = new Element('fancy_name', 'fancey_value_231AEruxu3$!@#');
        $child_element_2 = new Element('other', 3123);

        $element = new Element('test', null);
        $element->addChild($child_element);
        $element->addChild($child_element_2);

        $this->assertSame([
            'name' => 'test',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'fancy_name',
                    'value' => 'fancey_value_231AEruxu3$!@#',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'other',
                    'value' => 3123,
                    'attributes' => [],
                    'children' => [],
                ],
            ],
        ], $element->toArray());
    }

    /** @test */
    public function it_returns_valid_representation_of_object_when_nested_children_were_set()
    {
        $child_element = new Element('fancy_name', 'fancey_value_231AEruxu3$!@#');
        $child_element_2 = new Element('other', 3123);

        $child_element_3 = new Element('brand', 'new');
        $child_element_2->addChild($child_element_3);
        $child_element_2->addAttribute(new Attribute('one', 'foo'));
        $child_element_2->addAttribute(new Attribute('two', 'bar'));
        $child_element_3->addAttribute(new Attribute('three', 'baz'));

        $element = new Element('test', null);
        $element->addChild($child_element);
        $element->addChild($child_element_2);

        $this->assertSame([
            'name' => 'test',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'fancy_name',
                    'value' => 'fancey_value_231AEruxu3$!@#',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'other',
                    'value' => 3123,
                    'attributes' => [
                        [
                            'name' => 'one',
                            'value' => 'foo',
                        ],
                        [
                            'name' => 'two',
                            'value' => 'bar',
                        ],
                    ],
                    'children' => [
                        [
                            'name' => 'brand',
                            'value' => 'new',
                            'attributes' => [
                                [
                                    'name' => 'three',
                                    'value' => 'baz',
                                ],
                            ],
                            'children' => [],
                        ],
                    ],
                ],
            ],
        ], $element->toArray());
    }
}
