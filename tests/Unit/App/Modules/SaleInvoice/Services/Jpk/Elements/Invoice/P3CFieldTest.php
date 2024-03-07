<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P3CFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_company_name()
    {
        $company_name = 'Sample contractor name 23 "ABC';

        $invoice = $this->getDefaultInvoiceModel();

        $invoice->invoiceCompany->name = $company_name;

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_3C', $company_name);
    }
}
