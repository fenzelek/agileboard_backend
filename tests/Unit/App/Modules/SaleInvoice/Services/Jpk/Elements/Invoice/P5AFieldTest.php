<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\CountryVatinPrefix;
use Tests\Helpers\Jpk;
use Tests\TestCase;

class P5AFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_adds_field_with_valid_vatin_prefix()
    {
        $prefix_code = 'ABTEST';

        $invoice = $this->getDefaultInvoiceModel();

        $invoice->invoiceContractor->setRelation(
            'vatinPrefix',
            new CountryVatinPrefix(['key' => $prefix_code])
        );

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_5A', $prefix_code);
    }

    /** @test */
    public function it_doesnt_add_field_when_no_vatin_prefix()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->invoiceContractor->setRelation('vatinPrefix', null);

        $result = $this->buildAndCreateResult($invoice);

        $this->assertNull($this->findChildElement($result, 'tns:P5A'));
    }
}
