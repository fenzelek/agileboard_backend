<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Company as CompanyModel;

class P19FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_excluded_tax_to_false()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $company = new CompanyModel();
        $company->vat_payer = true;

        $result = $this->buildAndCreateResult($invoice, $company);

        $this->findAndVerifyField($result, 'tns:P_19', 'false');
    }

    /** @test */
    public function it_sets_excluded_tax_to_true_when_company_is_not_vat_payer()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $company = new CompanyModel();
        $company->vat_payer = false;

        $result = $this->buildAndCreateResult($invoice, $company);

        $this->findAndVerifyField($result, 'tns:P_19', 'true');
    }
}
