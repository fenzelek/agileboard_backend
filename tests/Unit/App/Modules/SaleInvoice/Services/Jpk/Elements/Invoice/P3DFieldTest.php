<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\Company;
use App\Models\Db\InvoiceCompany;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\Address;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceTaxes;
use Mockery;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceType as InvoiceTypeHelper;

class P3DFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_company_address()
    {
        $sample_vat_in = 'vat_in_just_to_compare_that_arg_is_valid';

        $invoice = $this->getDefaultInvoiceModel();
        $invoice->setRelation('invoiceCompany', new InvoiceCompany(['vatin' => $sample_vat_in]));

        $company_address = 'TEST Company address';

        $invoice_taxes = Mockery::mock(InvoiceTaxes::class);
        $invoice_taxes->shouldReceive('create')->andReturn([]);
        $address = Mockery::mock(Address::class);
        $address->shouldReceive('getCompanyAddress')->once()
            ->with(Mockery::on(function ($arg) use ($sample_vat_in) {
                return $arg instanceof InvoiceCompany && $arg->vatin == $sample_vat_in;
            }))->andReturn($company_address);

        $address->shouldReceive('getContractorAddress')->once()->andReturn('whatever contractor');

        $invoice_type = Mockery::mock(InvoiceTypeHelper::class);
        $invoice_type->shouldReceive('calculate')->andReturn('sample calculated type');

        $margin_procedure = $this->getDefaultMarginProcedure();

        $invoice_element = new Invoice($invoice_taxes, $address, $invoice_type, $margin_procedure);

        $company = new Company(['vat_payer' => true]);

        $result = $invoice_element->create($invoice, $company);

        $this->findAndVerifyField($result, 'tns:P_3D', $company_address);
    }
}
