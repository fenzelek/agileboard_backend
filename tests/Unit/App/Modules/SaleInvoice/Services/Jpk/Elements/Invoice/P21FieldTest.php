<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P21FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_tax_office_company_field_to_false()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_21', 'false');
    }
}
