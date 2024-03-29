<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\CountryVatinPrefix;
use Tests\Helpers\Jpk;
use Tests\TestCase;

class P5BFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_contractor_vatin()
    {
        $prefix_code = 'ABTEST';
        $vatin = '2312313221A4123';

        $invoice = $this->getDefaultInvoiceModel();

        $invoice->invoiceContractor->vatin = $vatin;
        $invoice->invoiceContractor->setRelation(
            'vatinPrefix',
            new CountryVatinPrefix(['key' => $prefix_code])
        );

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_5B', $vatin);
    }

    /** @test */
    public function it_sets_valid_contractor_vatin_when_no_vatin_prefix()
    {
        $vatin = '2312313221A4123';

        $invoice = $this->getDefaultInvoiceModel();

        $invoice->invoiceContractor->vatin = $vatin;
        $invoice->invoiceContractor->setRelation('vatinPrefix', null);

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_5B', $vatin);
    }
}
