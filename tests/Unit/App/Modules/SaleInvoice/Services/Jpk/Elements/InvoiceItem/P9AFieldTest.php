<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;

use App\Models\Db\InvoiceItem as InvoiceItemModel;
use App\Models\Db\ServiceUnit;
use App\Models\Db\VatRate;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;
use Mockery;

class P9AFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_returns_valid_net_price_for_net_price_field()
    {
        $invoice_item_model = Mockery::mock(InvoiceItemModel::class)->makePartial();
        $invoice_item_model->setRelation('serviceUnit', new ServiceUnit());
        $invoice_item_model->setRelation('vatRate', new VatRate());
        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_9A', '41823878.83');
    }

    /** @test */
    public function it_returns_valid_net_price_for_net_price_field_when_net_price_is_integer()
    {
        $invoice_item_model = Mockery::mock(InvoiceItemModel::class)->makePartial();
        $invoice_item_model->setRelation('serviceUnit', new ServiceUnit());
        $invoice_item_model->setRelation('vatRate', new VatRate());
        $invoice = new InvoiceModel();

        $invoice_item_helper = \Mockery::mock(\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem::class)->makePartial();
        $invoice_item_helper->shouldReceive('getRealRawNetPrice')->once()->withNoArgs()->andReturn(418238788300);
        $invoice_item_helper->shouldReceive('getRealRawBruttoPrice')->once()->withNoArgs()->andReturn(0);
        $invoice_item_helper->shouldReceive('getRealRawNetPriceSum')->once()->withNoArgs()->andReturn(0);

        $invoice_item = new InvoiceItem($invoice_item_helper);
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_9A', '4182387883.00');
    }
}
