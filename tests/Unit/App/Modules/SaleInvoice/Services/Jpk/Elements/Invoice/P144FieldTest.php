<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P144FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function there_is_empty_p14_4_field()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $result = $this->buildAndCreateResult($invoice);

        $this->assertNull($this->findChildElement($result, 'tns:P_14_4'));
    }

    /** @test */
    public function there_is_vat_sum_p14_4_field()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $result = $this->buildAndCreateResult($invoice);

        $this->assertNull($this->findChildElement($result, 'tns:P_13_4'));
    }
}
