<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\Company;
use App\Models\Db\InvoiceContractor;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\Address;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceTaxes;
use Mockery;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceType as InvoiceTypeHelper;

class P3BFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_contractor_address()
    {
        $sample_vat_in = 'vat_in_just_to_compare_that_arg_is_valid';

        $invoice = $this->getDefaultInvoiceModel();
        $invoice->setRelation(
            'invoiceContractor',
            new InvoiceContractor(['vatin' => $sample_vat_in])
        );
        $contractor_address = 'TEST contractor address';

        $invoice_taxes = Mockery::mock(InvoiceTaxes::class);
        $invoice_taxes->shouldReceive('create')->andReturn([]);
        $address = Mockery::mock(Address::class);
        $address->shouldReceive('getCompanyAddress')->once()->andReturn('whatever company');
        $address->shouldReceive('getContractorAddress')->once()
            ->with(Mockery::on(function ($arg) use ($sample_vat_in) {
                return $arg instanceof InvoiceContractor && $arg->vatin == $sample_vat_in;
            }))->andReturn($contractor_address);

        $invoice_type = Mockery::mock(InvoiceTypeHelper::class);
        $invoice_type->shouldReceive('calculate')->andReturn('sample calculated type');

        $margin_procedure = $this->getDefaultMarginProcedure();

        $invoice_element = new Invoice($invoice_taxes, $address, $invoice_type, $margin_procedure);

        $company = new Company(['vat_payer' => true]);

        $result = $invoice_element->create($invoice, $company);

        $this->findAndVerifyField($result, 'tns:P_3B', $contractor_address);
    }
}
