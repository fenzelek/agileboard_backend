<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceItem as ModelInvoiceItem;
use App\Models\Other\InvoiceCorrectionType;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem;
use Tests\TestCase;

class GetRealRawNetPriceTest extends TestCase
{
    /** @test */
    public function it_returns_net_price_if_it_is_set()
    {
        $price_net = 4182387883;

        $item = new ModelInvoiceItem(['price_net' => $price_net]);

        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;

        $this->assertSame($price_net, $invoice_item->getRealRawNetPrice());
    }

    /** @test */
    public function it_returns_valid_net_price_if_quantity_set_to_1()
    {
        $item = new ModelInvoiceItem(['price_net_sum' => 81, 'quantity' => 1000]);

        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;

        $this->assertSame(81, $invoice_item->getRealRawNetPrice());
    }

    /** @test */
    public function it_returns_valid_net_price_when_sum_cannot_be_divided_by_quantity()
    {
        $item = new ModelInvoiceItem(['price_net_sum' => 4065,'quantity' => 2000]);

        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;

        /* this is complex case because because 4065 cannot be divided by 2 */

        $this->assertSame(2033, $invoice_item->getRealRawNetPrice());
    }

    /** @test */
    public function it_returns_valid_net_price_when_quantity_is_half()
    {
        $item = new ModelInvoiceItem(['price_net_sum' => 30,'quantity' => 500]);

        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;

        $this->assertSame(60, $invoice_item->getRealRawNetPrice());
    }

    /** @test */
    public function it_returns_valid_net_price_when_correction_tax()
    {
        $position_corrected = new ModelInvoiceItem([
            'price_net_sum' => 1200,
            'quantity' => 1000,
        ]);
        $item = new ModelInvoiceItem([
            'price_net_sum' => 1200,
            'quantity' => 1000,
        ]);

        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;

        $item->setRelation('positionCorrected', $position_corrected);
        $invoice = new Invoice(['correction_type' => InvoiceCorrectionType::TAX]);

        $item->setRelation('invoice', $invoice);

        $this->assertSame(0, $invoice_item->getRealRawNetPrice());
    }

    /** @test */
    public function it_returns_valid_differ_between_prices_when_correction_prices()
    {
        $position_corrected = new ModelInvoiceItem([
            'price_net_sum' => 1200,
            'quantity' => 1000,
        ]);
        $item = new ModelInvoiceItem([
            'price_net_sum' => 600,
            'quantity' => 1000,
        ]);

        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;
        $invoice = new Invoice(['correction_type' => InvoiceCorrectionType::PRICE]);

        $item->setRelation('invoice', $invoice);
        $item->setRelation('positionCorrected', $position_corrected);

        $this->assertSame(600, $invoice_item->getRealRawNetPrice());
    }

    /** @test */
    public function it_returns_valid_differ_between_prices_and_quantity_when_correction_prices()
    {
        $position_corrected = new ModelInvoiceItem([
            'price_net_sum' => 1200,
            'quantity' => 2000,
        ]);
        $item = new ModelInvoiceItem([
            'price_net_sum' => 600,
            'quantity' => 1000,
        ]);

        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;
        $invoice = new Invoice(['correction_type' => InvoiceCorrectionType::PRICE]);

        $item->setRelation('invoice', $invoice);

        $item->setRelation('positionCorrected', $position_corrected);

        $this->assertSame(600, $invoice_item->getRealRawNetPrice());
    }
}
