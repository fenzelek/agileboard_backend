<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceType;
use Mockery;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class P23FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_true_when_invoice_claims_is_eu_triple_reverse_charge()
    {
        $invoice = Mockery::mock(InvoiceModel::class)->makePartial();
        $invoice_contractor = new InvoiceContractor();
        $invoice_contractor->setRelation('vatinPrefix', null);
        $invoice_company = new InvoiceCompany();
        $invoice_company->setRelation('vatinPrefix', null);

        $invoice->setRelation('invoiceContractor', $invoice_contractor);
        $invoice->setRelation('invoiceCompany', $invoice_company);
        $invoice->setRelation('invoiceType', new InvoiceType());
        $invoice->shouldReceive('isEuTripleReverseCharge')->once()->withNoArgs()->andReturn(true);

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_23', 'true');
    }

    /** @test */
    public function it_sets_false_when_invoice_claims_is_not__eu_triple_reverse_charge()
    {
        $invoice = Mockery::mock(InvoiceModel::class)->makePartial();
        $invoice_contractor = new InvoiceContractor();
        $invoice_contractor->setRelation('vatinPrefix', null);
        $invoice_company = new InvoiceCompany();
        $invoice_company->setRelation('vatinPrefix', null);

        $invoice->setRelation('invoiceContractor', $invoice_contractor);
        $invoice->setRelation('invoiceCompany', $invoice_company);
        $invoice->setRelation('invoiceType', new InvoiceType());
        $invoice->shouldReceive('isEuTripleReverseCharge')->once()->withNoArgs()->andReturn(false);

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_23', 'false');
    }
}
