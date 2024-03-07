<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;

use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class P2BFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_returns_valid_value_for_invoice_number_field()
    {
        $invoice_number = 'XTR23/2312312"ere>X';

        $invoice_item_model = $this->getDefaultInvoiceItemModel();
        $invoice = new InvoiceModel(['number' => $invoice_number]);

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_2B', $invoice_number);
    }
}
