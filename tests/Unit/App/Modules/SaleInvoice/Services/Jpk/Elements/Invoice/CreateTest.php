<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\Company;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\Address;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceTaxes;
use Mockery;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceType as InvoiceTypeHelper;

class CreateTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_returns_element_object()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $result = $this->buildAndCreateResult($invoice);

        $this->assertTrue($result instanceof Element);
    }

    /** @test */
    public function it_returns_valid_object()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->invoiceCompany->vatin = null;
        $invoice->invoiceContractor->vatin = null;

        $result = $this->buildAndCreateResult($invoice);

        $this->assertEquals([
            'name' => 'tns:Faktura',
            'value' => null,
            'attributes' => [
                [
                    'name' => 'typ',
                    'value' => 'G',
                ],
            ],
            'children' => [
                [
                    'name' => 'tns:P_1',
                    'value' => '',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_2A',
                    'value' => '',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_3A',
                    'value' => '',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_3B',
                    'value' => 'whatever contractor',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_3C',
                    'value' => null,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_3D',
                    'value' => 'whatever company',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_4B',
                    'value' => null,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_5B',
                    'value' => null,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_6',
                    'value' => null,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_15',
                    'value' => '0.00',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_16',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_17',
                    'value' => 'true',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_18',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_19',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_20',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_21',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_23',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_106E_2',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_106E_3',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:RodzajFaktury',
                    'value' => 'sample calculated type',
                    'attributes' => [],
                    'children' => [],
                ],
            ],

        ], $result->toArray());
    }

    /** @test */
    public function it_adds_taxes_field_when_tax_fields_returned()
    {
        $invoice = $this->getDefaultInvoiceModel();
        $invoice->invoiceCompany->vatin = null;
        $invoice->invoiceContractor->vatin = null;

        $invoice_taxes = Mockery::mock(InvoiceTaxes::class);
        $invoice_taxes->shouldReceive('create')->andReturn([
            new Element('foo', 'bar'),
            new Element('sample', 'example'),
        ]);

        $address = Mockery::mock(Address::class);
        $address->shouldReceive('getCompanyAddress')->once()->andReturn('sample company address');
        $address->shouldReceive('getContractorAddress')->once()->andReturn('sample contractor address');

        $invoice_type = Mockery::mock(InvoiceTypeHelper::class);
        $invoice_type->shouldReceive('calculate')->andReturn('sample calculated type');

        $margin_procedure = $this->getDefaultMarginProcedure();

        $invoice_element = new Invoice($invoice_taxes, $address, $invoice_type, $margin_procedure);

        $company = new Company(['vat_payer' => true]);

        $result = $invoice_element->create($invoice, $company);
        $this->assertEquals([
            'name' => 'tns:Faktura',
            'value' => null,
            'attributes' => [
                [
                    'name' => 'typ',
                    'value' => 'G',
                ],
            ],
            'children' => [
                [
                    'name' => 'tns:P_1',
                    'value' => '',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_2A',
                    'value' => '',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_3A',
                    'value' => '',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_3B',
                    'value' => 'sample contractor address',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_3C',
                    'value' => null,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_3D',
                    'value' => 'sample company address',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_4B',
                    'value' => null,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_5B',
                    'value' => null,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_6',
                    'value' => null,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'foo',
                    'value' => 'bar',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'sample',
                    'value' => 'example',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_15',
                    'value' => '0.00',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_16',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_17',
                    'value' => 'true',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_18',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_19',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_20',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_21',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_23',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_106E_2',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:P_106E_3',
                    'value' => 'false',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:RodzajFaktury',
                    'value' => 'sample calculated type',
                    'attributes' => [],
                    'children' => [],
                ],
            ],

        ], $result->toArray());
    }
}
