<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceTax;

use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\VatRate;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceTax;
use Tests\TestCase;
use  Illuminate\Database\Eloquent\Collection;

class CreateTest extends TestCase
{
    /** @test */
    public function it_creates_array_of_elements_of_valid_type()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);

        $tax_reports = new Collection([$invoice_tax_report]);

        $invoice_tax = new InvoiceTax();
        $elements = $invoice_tax->create($tax_reports, VatRate::TAX_23, 'a', 'b');
        $this->assertTrue(is_array($elements));
        $this->assertCount(2, $elements);
        $this->assertTrue($elements[0] instanceof Element);
        $this->assertTrue($elements[1] instanceof Element);
    }

    /** @test */
    public function it_creates_array_with_single_element_of_valid_type()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);

        $tax_reports = new Collection([$invoice_tax_report]);

        $invoice_tax = new InvoiceTax();
        $elements = $invoice_tax->create($tax_reports, VatRate::TAX_23, 'a');
        $this->assertTrue(is_array($elements));
        $this->assertCount(1, $elements);
        $this->assertTrue($elements[0] instanceof Element);
    }

    /** @test */
    public function it_makes_valid_calculation_when_string_rate_passed_and_elements_exist()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);
        $invoice_tax_report->price_net = '2243131';
        $invoice_tax_report->price_gross = '413223535';

        $tax_reports = new Collection([$invoice_tax_report]);

        $invoice_tax = new InvoiceTax();
        $elements = $invoice_tax->create($tax_reports, VatRate::TAX_23, 'foo', 'bar');

        $this->assertSame([
            'name' => 'foo',
            'value' => '22431.31',
            'attributes' => [],
            'children' => [],
        ], $elements[0]->toArray());

        $this->assertSame([
            'name' => 'bar',
            'value' => '4109804.04',
            'attributes' => [],
            'children' => [],
        ], $elements[1]->toArray());
    }

    /** @test */
    public function it_makes_valid_calculation_when_array_rate_passed_and_elements_exist()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);
        $invoice_tax_report->price_net = '2243131';
        $invoice_tax_report->price_gross = '413223535';

        $tax_reports = new Collection([$invoice_tax_report]);

        $invoice_tax = new InvoiceTax();
        $elements = $invoice_tax->create($tax_reports, [VatRate::TAX_23], 'foo', 'bar');

        $this->assertSame([
            'name' => 'foo',
            'value' => '22431.31',
            'attributes' => [],
            'children' => [],
        ], $elements[0]->toArray());

        $this->assertSame([
            'name' => 'bar',
            'value' => '4109804.04',
            'attributes' => [],
            'children' => [],
        ], $elements[1]->toArray());
    }

    /** @test */
    public function it_makes_valid_calculation_when_array_rate_passed_and_multiple_elements_match()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;

        $vat_rate_8 = new VatRate();
        $vat_rate_8->name = VatRate::TAX_8;

        $vat_rate_7 = new VatRate();
        $vat_rate_7->name = VatRate::TAX_7;

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);
        $invoice_tax_report->price_net = '2243131';
        $invoice_tax_report->price_gross = '413223535';

        $invoice_tax_report2 = new InvoiceTaxReport();
        $invoice_tax_report2->setRelation('vatRate', $vat_rate_7);
        $invoice_tax_report2->price_net = '423423';
        $invoice_tax_report2->price_gross = '154251';

        $invoice_tax_report3 = new InvoiceTaxReport();
        $invoice_tax_report3->setRelation('vatRate', $vat_rate_8);
        $invoice_tax_report3->price_net = '651432';
        $invoice_tax_report3->price_gross = '912737';

        $invoice_tax_report4 = new InvoiceTaxReport();
        $invoice_tax_report4->setRelation('vatRate', $vat_rate_23);
        $invoice_tax_report4->price_net = '8901231';
        $invoice_tax_report4->price_gross = '382198283';

        $tax_reports = new Collection([
            $invoice_tax_report,
            $invoice_tax_report2,
            $invoice_tax_report3,
            $invoice_tax_report4,
        ]);

        $invoice_tax = new InvoiceTax();
        $elements =
            $invoice_tax->create($tax_reports, [VatRate::TAX_23, VatRate::TAX_8], 'foo', 'bar');

        $this->assertSame([
            'name' => 'foo',
            'value' => '117957.94', //22431.31 + 6514.32 + 89012.31
            'attributes' => [],
            'children' => [],
        ], $elements[0]->toArray());

        $this->assertSame([
            'name' => 'bar',
            // (4132235.35 - 22431.31) + (9127.37 - 6514.32) + (3821982.83 - 89012.31)
            'value' => '7845387.61',
            'attributes' => [],
            'children' => [],
        ], $elements[1]->toArray());
    }

    /** @test */
    public function it_makes_valid_calculation_one_array_without_tax_field_when_rate_passed_and_multiple_elements_match()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;

        $vat_rate_8 = new VatRate();
        $vat_rate_8->name = VatRate::TAX_8;

        $vat_rate_7 = new VatRate();
        $vat_rate_7->name = VatRate::TAX_7;

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);
        $invoice_tax_report->price_net = '2243131';
        $invoice_tax_report->price_gross = '413223535';

        $invoice_tax_report2 = new InvoiceTaxReport();
        $invoice_tax_report2->setRelation('vatRate', $vat_rate_7);
        $invoice_tax_report2->price_net = '423423';
        $invoice_tax_report2->price_gross = '154251';

        $invoice_tax_report3 = new InvoiceTaxReport();
        $invoice_tax_report3->setRelation('vatRate', $vat_rate_8);
        $invoice_tax_report3->price_net = '651432';
        $invoice_tax_report3->price_gross = '912737';

        $invoice_tax_report4 = new InvoiceTaxReport();
        $invoice_tax_report4->setRelation('vatRate', $vat_rate_23);
        $invoice_tax_report4->price_net = '8901231';
        $invoice_tax_report4->price_gross = '382198283';

        $tax_reports = new Collection([
            $invoice_tax_report,
            $invoice_tax_report2,
            $invoice_tax_report3,
            $invoice_tax_report4,
        ]);

        $invoice_tax = new InvoiceTax();
        $elements = $invoice_tax->create($tax_reports, [VatRate::TAX_23, VatRate::TAX_8], 'foo');

        $this->assertCount(1, $elements);

        $this->assertSame([
            'name' => 'foo',
            'value' => '117957.94', //22431.31 + 6514.32 + 89012.31
            'attributes' => [],
            'children' => [],
        ], $elements[0]->toArray());
    }

    /** @test */
    public function it_returns_empty_array_when_no_matching_taxes_found()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);
        $invoice_tax_report->price_net = '2243131';
        $invoice_tax_report->price_gross = '413223535';

        $tax_reports = new Collection([$invoice_tax_report]);

        $invoice_tax = new InvoiceTax();
        $elements = $invoice_tax->create($tax_reports, [VatRate::TAX_8], 'foo', 'bar');

        $this->assertSame([], $elements);
    }

    /** @test */
    public function it_returns_empty_array_when_no_matching_taxes_found_without_tax_field()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);
        $invoice_tax_report->price_net = '2243131';
        $invoice_tax_report->price_gross = '413223535';

        $tax_reports = new Collection([$invoice_tax_report]);

        $invoice_tax = new InvoiceTax();
        $elements = $invoice_tax->create($tax_reports, [VatRate::TAX_8], 'foo');

        $this->assertSame([], $elements);
    }

    /** @test */
    public function it_returns_valid_empty_array_in_second_call_to_make_sure_previous_calculations_are_cleared()
    {
        $vat_rate_23 = new VatRate();
        $vat_rate_23->name = VatRate::TAX_23;

        $invoice_tax_report = new InvoiceTaxReport();
        $invoice_tax_report->setRelation('vatRate', $vat_rate_23);
        $invoice_tax_report->price_net = '2243131';
        $invoice_tax_report->price_gross = '413223535';

        $tax_reports = new Collection([$invoice_tax_report]);

        $invoice_tax = new InvoiceTax();
        // here we make 1st call and don't care about result
        $elements = $invoice_tax->create($tax_reports, VatRate::TAX_23, 'foo', 'bar');

        // here we want to make sure result is as expected (in this case it's empty array)
        $elements = $invoice_tax->create($tax_reports, [VatRate::TAX_8], 'foo');
        $this->assertSame([], $elements);
    }
}
