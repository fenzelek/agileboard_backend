<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P16FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_taxing_method()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_16', 'false');
    }
}
