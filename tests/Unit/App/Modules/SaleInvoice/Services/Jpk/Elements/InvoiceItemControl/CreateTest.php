<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItemControl;

use App\Models\Db\InvoiceItem;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem as HelperInvoiceItem;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItemControl;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class CreateTest extends TestCase
{
    /** @test */
    public function it_creates_element_of_valid_type()
    {
        $invoice_item_control = new InvoiceItemControl(new HelperInvoiceItem());
        $element = $invoice_item_control->create(new Collection());
        $this->assertTrue($element instanceof Element);
    }

    /** @test */
    public function it_creates_element_with_valid_content_when_no_invoices()
    {
        $invoice_item_control = new InvoiceItemControl(new HelperInvoiceItem());
        $element = $invoice_item_control->create(new Collection());

        $this->assertSame([
            'name' => 'tns:FakturaWierszCtrl',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'tns:LiczbaWierszyFaktur',
                    'value' => 0,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:WartoscWierszyFaktur',
                    'value' => '0.00',
                    'attributes' => [],
                    'children' => [],
                ],
            ],
        ], $element->toArray());
    }

    /** @test */
    public function it_creates_element_with_valid_content_when_there_is_single_invoice_with_single_item()
    {
        $invoice_1 = new InvoiceModel(['price_gross' => 5728939212]);
        $invoice_1->setRelation('items', new Collection([
            new InvoiceItem(['price_net_sum' => 5126171231]),
        ]));

        $invoice_item_control = new InvoiceItemControl(new HelperInvoiceItem());
        $element = $invoice_item_control->create(new Collection([
            $invoice_1,
        ]));

        $this->assertSame([
            'name' => 'tns:FakturaWierszCtrl',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'tns:LiczbaWierszyFaktur',
                    'value' => 1,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:WartoscWierszyFaktur',
                    'value' => '51261712.31',
                    'attributes' => [],
                    'children' => [],
                ],
            ],
        ], $element->toArray());
    }

    /** @test */
    public function it_creates_element_with_zero_value_invoices_when_invoice_corrected()
    {
        $invoice_1 = new InvoiceModel(['price_gross' => 5728939212, 'correction_type' => InvoiceCorrectionType::TAX]);
        $corrected_item = new InvoiceItem(['price_net_sum' => 5126171231]);
        $invoice_item = new InvoiceItem(['price_net_sum' => 5126171231]);
        $invoice_item->setRelation('positionCorrected', $corrected_item);
        $invoice_item->setRelation('invoice', $invoice_1);

        $invoice_1->setRelation('items', new Collection([
            $invoice_item,
        ]));

        $invoice_item_control = new InvoiceItemControl(new HelperInvoiceItem());
        $element = $invoice_item_control->create(new Collection([
            $invoice_1,
        ]));

        $this->assertSame([
            'name' => 'tns:FakturaWierszCtrl',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'tns:LiczbaWierszyFaktur',
                    'value' => 1,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:WartoscWierszyFaktur',
                    'value' => '0.00',
                    'attributes' => [],
                    'children' => [],
                ],
            ],
        ], $element->toArray());
    }

    /** @test */
    public function it_creates_element_with_valid_content_when_there_are_multiple_invoice_with_multiple_items()
    {
        $invoice_1 = new InvoiceModel(['price_gross' => 5728939212]);
        $invoice_1->setRelation('items', new Collection([
            new InvoiceItem(['price_net_sum' => 5126171231]),
            new InvoiceItem(['price_net_sum' => 3123123]),
        ]));

        $invoice_2 = new InvoiceModel(['price_gross' => 5728939212]);
        $invoice_2->setRelation('items', new Collection([
            new InvoiceItem(['price_net_sum' => -312351]),
            new InvoiceItem(['price_net_sum' => 9135723]),
            new InvoiceItem(['price_net_sum' => 9301784]),
        ]));

        $invoice_3 = new InvoiceModel(['price_gross' => 5728939212]);
        $invoice_3->setRelation('items', new Collection([
            new InvoiceItem(['price_net_sum' => -17341]),
            new InvoiceItem(['price_net_sum' => 8918391]),
            new InvoiceItem(['price_net_sum' => 4619043]),
        ]));

        $invoice_4 = new InvoiceModel(['price_gross' => 5728939212, 'correction_type' => InvoiceCorrectionType::TAX]);
        $corrected_item = new InvoiceItem(['price_net_sum' => 5126171231]);
        $invoice_item = new InvoiceItem(['price_net_sum' => 5126171231]);
        $invoice_item->setRelation('positionCorrected', $corrected_item);
        $invoice_item->setRelation('invoice', $invoice_4);
        $invoice_4->setRelation('items', new Collection([
            $invoice_item,
        ]));

        $invoice_item_control = new InvoiceItemControl(new HelperInvoiceItem());
        $element = $invoice_item_control->create(new Collection([
            $invoice_1, $invoice_2, $invoice_3, $invoice_4,
        ]));

        $this->assertSame([
            'name' => 'tns:FakturaWierszCtrl',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'tns:LiczbaWierszyFaktur',
                    'value' => 9,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:WartoscWierszyFaktur',
                    'value' => '51609396.03',
                    'attributes' => [],
                    'children' => [],
                ],
            ],
        ], $element->toArray());
    }
}
