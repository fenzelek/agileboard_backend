<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;

use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class CreateTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_returns_element_object()
    {
        $invoice_item_model = $this->getDefaultInvoiceItemModel();
        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->assertTrue($result instanceof Element);
    }

    /** @test */
    public function it_returns_valid_object()
    {
        $invoice_item_model = $this->getDefaultInvoiceItemModel();
        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->assertEquals([
            'name' => 'tns:FakturaWiersz',
            'value' => null,
            'attributes' => [
                [
                    'name' => 'typ',
                    'value' => 'G',
                ],
            ],
            'children' => [
                [
                    'name' => 'tns:P_2B',
                    'value' => '',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_7',
                    'value' => '',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_8A',
                    'value' => null,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_8B',
                    'value' => '1.0000',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_9A',
                    'value' => '41823878.83',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_9B',
                    'value' => '0.00',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_11',
                    'value' => '4182387883.00',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_12',
                    'value' => '',
                    'attributes' => [],
                    'children' => [],
                ],
            ],

        ], $result->toArray());
    }
}
