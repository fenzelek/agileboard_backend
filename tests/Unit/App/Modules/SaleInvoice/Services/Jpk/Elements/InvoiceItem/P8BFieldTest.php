<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;

use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class P8BFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_returns_valid_quantity_when_quantity_is_float()
    {
        $invoice_item_model = $this->getDefaultInvoiceItemModel();

        $invoice_item_model->fill(['quantity' => 4182387883]);

        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_8B', '4182387.8830');
    }

    /** @test */
    public function it_returns_valid_quantity_when_quantity_is_integer()
    {
        $invoice_item_model = $this->getDefaultInvoiceItemModel();

        $invoice_item_model->fill(['quantity' => 4182387000]);

        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_8B', '4182387.0000');
    }
}
