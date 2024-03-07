<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;

use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class P7FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_returns_custom_name_when_its_set_for_service_name_field()
    {
        $name = 'Test service';
        $custom_name = 'Customized name of service';

        $invoice_item_model = $this->getDefaultInvoiceItemModel();
        $invoice_item_model->fill(['name' => $name, 'custom_name' => $custom_name]);

        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_7', $custom_name);
    }

    /** @test */
    public function it_returns_name_when_no_custom_name_set_for_service_name_field()
    {
        $name = 'Test service';
        $custom_name = null;

        $invoice_item_model = $this->getDefaultInvoiceItemModel();
        $invoice_item_model->fill(['name' => $name, 'custom_name' => $custom_name]);

        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_7', $name);
    }
}
