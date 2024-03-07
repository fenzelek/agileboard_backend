<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Root;

use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Root;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @test */
    public function it_creates_element_of_valid_type()
    {
        $root = new Root();
        $element = $root->create();
        $this->assertTrue($element instanceof Element);
    }

    /** @test */
    public function it_creates_element_with_valid_content()
    {
        $root = new Root();
        $element = $root->create();
        $this->assertSame(
            [
            'name' => 'tns:JPK',
            'value' => null,
            'attributes' => [
                [
                    'name' => 'xsi:schemaLocation',
                    'value' => 'http://jpk.mf.gov.pl/wzor/2016/03/09/03095/ http://www.mf.gov.pl/documents/764034/5134536/Schemat_JPK_FA(1)_v1-0.xsd',
                ],
                [
                    'name' => 'xmlns:tns',
                    'value' => 'http://jpk.mf.gov.pl/wzor/2016/03/09/03095/',
                ],
                [
                    'name' => 'xmlns:xsi',
                    'value' => 'http://www.w3.org/2001/XMLSchema-instance',
                ],
                [
                    'name' => 'xmlns:etd',
                    'value' => 'http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2016/01/25/eD/DefinicjeTypy/',
                ],
                [
                    'name' => 'xmlns:kck',
                    'value' => 'http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2013/05/23/eD/KodyCECHKRAJOW/',
                ],
                [
                    'name' => 'xmlns:xsd',
                    'value' => 'http://www.w3.org/2001/XMLSchema',
                ],
                [
                    'name' => 'xmlns:msxsl',
                    'value' => 'urn:schemas-microsoft-com:xslt',
                ],
                [
                    'name' => 'xmlns:usr',
                    'value' => 'urn:the-xml-files:xslt',
                ],
            ],
            'children' => [],
        ],
            $element->toArray()
        );
    }
}
