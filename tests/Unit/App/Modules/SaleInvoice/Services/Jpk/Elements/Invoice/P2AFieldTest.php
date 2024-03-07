<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P2AFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_invoice_number()
    {
        $invoice_number = 'SAMPLE/INVOICE/123/3123/31231"X';

        $invoice = $this->getDefaultInvoiceModel();
        $invoice->number = $invoice_number;

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_2A', $invoice_number);
    }
}
