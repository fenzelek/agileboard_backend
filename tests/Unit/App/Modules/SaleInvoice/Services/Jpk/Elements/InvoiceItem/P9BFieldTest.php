<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;

use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class P9BFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_returns_valid_value_for_gross_price_field()
    {
        $invoice_item_model = $this->getDefaultInvoiceItemModel();

        $invoice_item_model->fill(['price_net' => 1232681627123]);

        $invoice = new InvoiceModel();

        $invoice_item_helper = \Mockery::mock(\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem::class)->makePartial();
        $invoice_item_helper->shouldReceive('getRealRawNetPrice')->once()->withNoArgs()->andReturn(4182387883);
        $invoice_item_helper->shouldReceive('getRealRawBruttoPrice')->once()->withNoArgs()->andReturn(1232681627123);
        $invoice_item_helper->shouldReceive('getRealRawNetPriceSum')->once()->withNoArgs()->andReturn(418238788300);

        $invoice_item = new InvoiceItem($invoice_item_helper);
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_9B', '12326816271.23');
    }

    /** @test */
    public function it_returns_valid_value_for_gross_price_field_when_gross_price_is_integer()
    {
        $invoice_item_model = $this->getDefaultInvoiceItemModel();

        $invoice_item_model->fill(['price_net' => 123268162712300]);

        $invoice = new InvoiceModel();

        $invoice_item_helper = \Mockery::mock(\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem::class)->makePartial();
        $invoice_item_helper->shouldReceive('getRealRawNetPrice')->once()->withNoArgs()->andReturn(4182387883);
        $invoice_item_helper->shouldReceive('getRealRawBruttoPrice')->once()->withNoArgs()->andReturn(123268162712300);
        $invoice_item_helper->shouldReceive('getRealRawNetPriceSum')->once()->withNoArgs()->andReturn(418238788300);

        $invoice_item = new InvoiceItem($invoice_item_helper);
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_9B', '1232681627123.00');
    }
}
