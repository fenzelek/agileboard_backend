<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceTaxes;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\InvoiceType;
use App\Models\Db\VatRate;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceTax;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceTaxes;
use Mockery;
use Tests\TestCase;
use  Illuminate\Database\Eloquent\Collection;

class CreateTest extends TestCase
{
    /**
     * @var array
     */
    protected $elements;

    /** @test */
    public function it_calls_valid_invoice_tax_methods_and_return_valid_array()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;
        $this->createElements();

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);

        $tax_report_item = new InvoiceTaxReport();
        $tax_report_item->id = 1;
        $tax_report_item2 = new InvoiceTaxReport();
        $tax_report_item2->id = 41231;

        $tax_reports = new Collection([$tax_report_item, $tax_report_item2]);
        $invoice = new Invoice();
        $invoice->setRelation('taxes', $tax_reports);
        $invoice_type = new InvoiceType(['slug' => InvoiceTypeStatus::REVERSE_CHARGE]);
        $invoice->setRelation('invoiceType', $invoice_type);

        $invoice_tax = Mockery::mock(InvoiceTax::class);
        $this->setExpectations($invoice, $invoice_tax, $tax_reports);
        $invoice_taxes = new InvoiceTaxes($invoice_tax);

        $elements = $invoice_taxes->create($invoice);
        $this->assertTrue(is_array($elements));
        $this->assertCount(count($this->getElements()), $elements);

        foreach ($this->getElements() as $index => $element) {
            $this->assertTrue($elements[$index] instanceof Element);
            $this->assertSame($element, $elements[$index]);
        }
    }

    /** @test */
    public function it_clears_elements_in_second_call()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;
        $this->createElements();

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);

        $tax_report_item = new InvoiceTaxReport();
        $tax_report_item->id = 1;
        $tax_report_item2 = new InvoiceTaxReport();
        $tax_report_item2->id = 41231;

        $tax_reports = new Collection([$tax_report_item, $tax_report_item2]);
        $invoice = new Invoice();
        $invoice->setRelation('taxes', $tax_reports);
        $invoice_type = new InvoiceType(['slug' => InvoiceTypeStatus::MARGIN]);
        $invoice->setRelation('invoiceType', $invoice_type);
        $invoice_tax = Mockery::mock(InvoiceTax::class);
        $this->setExpectations($invoice, $invoice_tax, $tax_reports);
        $invoice_taxes = new InvoiceTaxes($invoice_tax);

        // 1st call - we don't care about result
        $invoice_taxes->create($invoice);

        // 2nd call

        $tax_reports2 = new Collection();
        $invoice2 = new Invoice();
        $invoice2->setRelation('taxes', $tax_reports2);
        $invoice2->setRelation('invoiceType', $invoice_type);
        $invoice_tax = Mockery::mock(InvoiceTax::class);
        $invoice_taxes = new InvoiceTaxes($invoice_tax);
        $this->setExpectations($invoice2, $invoice_tax, $tax_reports2, true);
        $result = $invoice_taxes->create($invoice2);
        $this->assertSame([], $result);
    }

    protected function getExpectationsArray($invoice)
    {
        $expectation = [
            [
                'taxes' => [VatRate::TAX_22, VatRate::TAX_23],
                'net_field' => 'tns:P_13_1',
                'tax_field' => 'tns:P_14_1',
                'elements' => [$this->getElements()[0], $this->getElements()[1]],
            ],
            [
                'taxes' => [VatRate::TAX_7, VatRate::TAX_8],
                'net_field' => 'tns:P_13_2',
                'tax_field' => 'tns:P_14_2',
                'elements' => [$this->getElements()[2]],
            ],
            [
                'taxes' => VatRate::TAX_5,
                'net_field' => 'tns:P_13_3',
                'tax_field' => 'tns:P_14_3',
                'elements' => [$this->getElements()[3], $this->getElements()[4]],
            ],
            [
                'taxes' => [
                    VatRate::TAX_0,
                    VatRate::TAX_0_WDT,
                    VatRate::TAX_0_EXP,
                ],
                'net_field' => 'tns:P_13_6',
                'tax_field' => null,
                'elements' => [$this->getElements()[7]],
            ],
            [
                'taxes' => VatRate::ZW,
                'net_field' => 'tns:P_13_7',
                'tax_field' => null,
                'elements' => [$this->getElements()[8]],
            ],
        ];
        if ($invoice->invoiceType->isReverseChargeType()) {
            $expectation[] = [
                'taxes' => [VatRate::NP, VatRate::NP_UE],
                'net_field' => 'tns:P_13_4',
                'tax_field' => 'tns:P_14_4',
                'elements' => [$this->getElements()[5], $this->getElements()[6]],
            ];
        }

        if ($invoice->invoiceType->isMarginType()) {
            $expectation[] = [
                'taxes' => [VatRate::NP, VatRate::NP_UE],
                'net_field' => 'tns:P_13_5',
                'tax_field' => 'tns:P_14_5',
                'elements' => [$this->getElements()[5], $this->getElements()[6]],
            ];
        }

        return $expectation;
    }

    protected function createElements()
    {
        $this->elements = [
            new Element('foo', 'bar'),
            new Element('test', 'element'),
            new Element('my', 'test'),
            new Element('sample', 'example'),
            new Element('other', 'fancy element'),
            new Element('np', 'NP'),
            new Element('eu_np', 'EN_NP'),
            new Element('ghi', 'JKL'),
            new Element('ghi', 'JKL'),
        ];
    }

    protected function getElements()
    {
        return $this->elements;
    }

    private function setExpectations($invoice, $invoice_tax, $tax_reports, $use_empty_return = false)
    {
        $expectations = $this->getExpectationsArray($invoice);
        foreach ($expectations as $expectation) {
            $invoice_tax->shouldReceive('create')->once()
                ->with(Mockery::on(function ($arg) use ($tax_reports) {
                    return $arg instanceof Collection && $arg->count() == $tax_reports->count() &&
                        $arg->all() == $tax_reports->all();
                }), $expectation['taxes'], $expectation['net_field'], $expectation['tax_field'])
                ->andReturn($use_empty_return ? [] : $expectation['elements']);
        }
    }
}
