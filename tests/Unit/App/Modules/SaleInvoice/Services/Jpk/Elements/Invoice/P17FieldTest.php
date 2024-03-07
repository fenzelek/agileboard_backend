<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P17FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_self_invoicing_value()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_17', 'false');
    }

    /** @test */
    public function it_sets_true_for_self_invoicing()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->invoiceCompany->vatin = $invoice->invoiceContractor->vatin;

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_17', 'true');
    }
}
