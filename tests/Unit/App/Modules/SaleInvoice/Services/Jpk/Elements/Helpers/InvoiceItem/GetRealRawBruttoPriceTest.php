<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceItem as ModelInvoiceItem;
use App\Models\Other\InvoiceCorrectionType;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem;
use Tests\TestCase;

class GetRealRawBruttoPriceTest extends TestCase
{
    /** @test */
    public function it_returns_brutto_price_if_quantity_set_to_1()
    {
        $price_net = 1000;

        $item = new ModelInvoiceItem(['price_net_sum' => $price_net, 'quantity' => 1000, 'vat_sum' => 100]);

        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;

        $this->assertSame(1100, $invoice_item->getRealRawBruttoPrice());
    }

    /** @test */
    public function it_returns_valid_gross_price_when_sum_cannot_be_divided_by_quantity()
    {
        $item = new ModelInvoiceItem(['price_net_sum' => 4065,'quantity' => 2000, 'vat_sum' => 100]);

        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;

        $this->assertSame(2083, $invoice_item->getRealRawBruttoPrice());
    }

    /** @test */
    public function it_returns_valid_price_price_when_quantity_is_half()
    {
        $item = new ModelInvoiceItem(['price_net_sum' => 30,'quantity' => 500, 'vat_sum' => 100]);
        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;

        $this->assertSame(260, $invoice_item->getRealRawBruttoPrice());
    }

    /** @test */
    public function it_returns_valid_gross_price_when_correction_tax()
    {
        $position_corrected = new ModelInvoiceItem([
            'price_net_sum' => 1200,
            'quantity' => 1000,
            'vat_sum' => 50,
        ]);
        $item = new ModelInvoiceItem([
            'price_net_sum' => 1200,
            'quantity' => 1000,
            'vat_sum' => 100,
        ]);
        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;

        $invoice = new Invoice(['correction_type' => InvoiceCorrectionType::TAX]);

        $item->setRelation('invoice', $invoice);
        $item->setRelation('positionCorrected', $position_corrected);

        $this->assertSame(50, $invoice_item->getRealRawBruttoPrice());
    }

    /** @test */
    public function it_returns_valid_differ_between_prices_when_correction_prices()
    {
        $position_corrected = new ModelInvoiceItem([
            'price_net_sum' => 1200,
            'quantity' => 1000,
            'vat_sum' => 100,
        ]);
        $item = new ModelInvoiceItem([
            'price_net_sum' => 600,
            'quantity' => 1000,
            'vat_sum' => 50,
        ]);

        $invoice_item = new InvoiceItem();
        $invoice_item->item = $item;

        $invoice = new Invoice(['correction_type' => InvoiceCorrectionType::PRICE]);

        $item->setRelation('invoice', $invoice);

        $item->setRelation('positionCorrected', $position_corrected);

        $this->assertSame(650, $invoice_item->getRealRawBruttoPrice());
    }
}
