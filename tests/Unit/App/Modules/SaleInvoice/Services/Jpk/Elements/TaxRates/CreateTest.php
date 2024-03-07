<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\TaxRates;

use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\TaxRates;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @test */
    public function it_creates_element_of_valid_type()
    {
        $tax_rates = new TaxRates();
        $element = $tax_rates->create();
        $this->assertTrue($element instanceof Element);
    }

    /** @test */
    public function it_creates_element_with_valid_content()
    {
        $tax_rates = new TaxRates();
        $element = $tax_rates->create();

        $this->assertSame(
            [
            'name' => 'tns:StawkiPodatku',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'tns:Stawka1',
                    'value' => '0.23',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:Stawka2',
                    'value' => '0.08',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:Stawka3',
                    'value' => '0.05',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:Stawka4',
                    'value' => '0.00',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:Stawka5',
                    'value' => '0.00',
                    'attributes' => [],
                    'children' => [],
                ],
            ],

        ],
            $element->toArray()
        );
    }
}
