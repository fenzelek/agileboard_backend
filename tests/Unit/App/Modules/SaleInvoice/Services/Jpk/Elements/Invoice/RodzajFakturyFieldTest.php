<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use Mockery;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceType as InvoiceTypeHelper;

class RodzajFakturyFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_type_of_invoice_to_vat_for_vat_invoice()
    {
        $calculated_invoice_type = 'sample calculated type';

        $invoice_type = Mockery::mock(InvoiceTypeHelper::class);
        $invoice_type->shouldReceive('calculate')->andReturn($calculated_invoice_type);

        $invoice = $this->getDefaultInvoiceModel();

        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::VAT])
        );

        $result = $this->buildAndCreateResult($invoice, null, $invoice_type);

        $this->findAndVerifyField($result, 'tns:RodzajFaktury', $calculated_invoice_type);
    }
}
