<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\Jpk;
use Tests\TestCase;

class ZALPodatekFieldTest extends TestCase
{
    use Jpk;
    use DatabaseTransactions;

    /** @test */
    public function it_doesnt_add_ZALPodatek_field_for_non_advance_invoice()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->setRelation('proforma', null);

        $result = $this->buildAndCreateResult($invoice);

        $this->assertNull($this->findChildElement($result, 'tns:ZALPodatek'));
    }

    /** @test */
    public function it_sets_ZALPodatek_field_when_advance_invoice()
    {
        $invoice = $this->getDefaultInvoiceModel();
        $this->setUpPropertiesForAdvance($invoice, InvoiceTypeStatus::ADVANCE);

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:ZALPodatek', '4.60');
    }

    /** @test */
    public function it_sets_ZALPodatek_field_when_final_advance_invoice()
    {
        $invoice = $this->getDefaultInvoiceModel();
        $this->setUpPropertiesForAdvance($invoice, InvoiceTypeStatus::FINAL_ADVANCE);

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:ZALPodatek', '4.60');
    }
}
