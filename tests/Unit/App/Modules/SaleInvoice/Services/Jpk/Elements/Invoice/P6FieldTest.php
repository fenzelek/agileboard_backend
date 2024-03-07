<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P6FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_sale_date()
    {
        $sale_date = '2017-03-12';

        $invoice = $this->getDefaultInvoiceModel();

        $invoice->sale_date = $sale_date;

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_6', $sale_date);
    }
}
