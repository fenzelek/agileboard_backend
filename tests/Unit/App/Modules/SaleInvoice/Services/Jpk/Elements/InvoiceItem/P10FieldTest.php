<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;

use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class P10FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_doesnt_add_discount_field_at_all()
    {
        $invoice_item_model = $this->getDefaultInvoiceItemModel();

        $invoice_item_model->fill(['price_gross' => 1232681627123]);

        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->assertNull($this->findChildElement($result, 'tns:P_10'));
    }
}
