<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;

use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class P11FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_returns_valid_value_for_total_net_price_field()
    {
        $invoice_item_model = $this->getDefaultInvoiceItemModel();

        $invoice_item_model->fill(['price_net_sum' => 1232681627123]);

        $invoice = new InvoiceModel();

        $invoice_item_helper = \Mockery::mock(\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem::class)->makePartial();
        $invoice_item_helper->shouldReceive('getRealRawNetPrice')->once()->withNoArgs()->andReturn(0);
        $invoice_item_helper->shouldReceive('getRealRawBruttoPrice')->once()->withNoArgs()->andReturn(0);
        $invoice_item_helper->shouldReceive('getRealRawNetPriceSum')->once()->withNoArgs()->andReturn(1232681627123);

        $invoice_item = new InvoiceItem($invoice_item_helper);
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_11', '12326816271.23');
    }

    /** @test */
    public function it_returns_valid_value_for_total_net_price_field_when_total_net_price_is_integer()
    {
        $invoice_item_model = $this->getDefaultInvoiceItemModel();

        $invoice_item_model->fill(['price_net_sum' => -123268162712300]);

        $invoice_item_helper = \Mockery::mock(\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem::class)->makePartial();
        $invoice_item_helper->shouldReceive('getRealRawNetPrice')->once()->withNoArgs()->andReturn(0);
        $invoice_item_helper->shouldReceive('getRealRawBruttoPrice')->once()->withNoArgs()->andReturn(0);
        $invoice_item_helper->shouldReceive('getRealRawNetPriceSum')->once()->withNoArgs()->andReturn(-123268162712300);

        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($invoice_item_helper);
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_11', '-1232681627123.00');
    }
}
