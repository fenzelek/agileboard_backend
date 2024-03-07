<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P3AFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_contractor_name()
    {
        $contractor_name = 'Sample contractor name 23 "ABC';

        $invoice = $this->getDefaultInvoiceModel();

        $invoice->invoiceContractor->name = $contractor_name;

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_3A', $contractor_name);
    }
}
