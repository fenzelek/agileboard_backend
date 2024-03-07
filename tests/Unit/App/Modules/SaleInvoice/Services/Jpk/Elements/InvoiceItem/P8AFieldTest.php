<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;

use App\Models\Db\ServiceUnit;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class P8AFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_returns_selected_service_unit_slug_for_service_unit_name_field()
    {
        $service_unit_slug = 'CUSTOM Service Unit value';

        $invoice_item_model = $this->getDefaultInvoiceItemModel();
        $invoice_item_model->setRelation(
            'serviceUnit',
            new ServiceUnit(['slug' => $service_unit_slug])
        );
        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_8A', $service_unit_slug);
    }
}
