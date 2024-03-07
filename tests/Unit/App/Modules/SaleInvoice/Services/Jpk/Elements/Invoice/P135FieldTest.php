<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P135FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function there_is_no_p13_5_field()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $result = $this->buildAndCreateResult($invoice);

        $this->assertNull($this->findChildElement($result, 'tns:P_13_5'));
    }
}
