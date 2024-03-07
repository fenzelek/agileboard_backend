<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P15FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_total_gross_price()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->price_gross = 78923113;

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_15', '789231.13');
    }
}
