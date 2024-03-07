<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\TaxesFiller;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\VatRate;
use App\Models\Other\SaleReport\Optima\TaxItem;
use App\Modules\SaleReport\Services\Optima\TaxesFiller;
use Tests\TestCase;

class CalculateTest extends TestCase
{
    /** @test */
    public function it_returns_empty_array_when_no_taxes()
    {
        $invoice = new Invoice();

        $invoice->setRelation('taxes', collect());

        $filler = new TaxesFiller();

        $this->assertSame([], $filler->calculate($invoice));
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_23_rate()
    {
        $this->verifyForRate('23%', 23, 0);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_8_rate()
    {
        $this->verifyForRate('8%', 8, 0);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_5_rate()
    {
        $this->verifyForRate('5%', 5, 0);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_0_rate()
    {
        $this->verifyForRate('0%', 0, 0);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_0wdt_rate()
    {
        $this->verifyForRate('0% WDT', 0, 0);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_0exp_rate()
    {
        $this->verifyForRate('0% EXP', 0, 0);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_np_ue_rate()
    {
        $this->verifyForRate('np. UE', 0, 4);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_np_rate()
    {
        $this->verifyForRate('np.', 0, 4);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_zw_rate()
    {
        $this->verifyForRate('zw.', 0, 1);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_22_rate()
    {
        $this->verifyForRate('22%', 22, 0);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_7_rate()
    {
        $this->verifyForRate('7%', 7, 0);
    }

    /** @test */
    public function it_returns_valid_one_array_element_for_3_rate()
    {
        $this->verifyForRate('3%', 3, 0);
    }

    /** @test */
    public function it_calculate_valid_vat_for_normal_invoice()
    {
        $this->verifyVat(5000, 20000, 15000);
    }

    /** @test */
    public function it_calculate_valid_vat_for_correction_invoice()
    {
        $this->verifyVat(-5000, -20000, -15000);
    }

    /** @test */
    public function it_return_valid_multi_element_array_when_multiple_taxes()
    {
        $invoice = new Invoice();

        $vat_rate = new VatRate();
        $vat_rate->name = '23%';
        $vat_rate->rate = 23;

        $vat_rate2 = new VatRate();
        $vat_rate2->name = VatRate::NP;
        $vat_rate2->rate = 0;

        $vat_rate3 = new VatRate();
        $vat_rate3->name = VatRate::NP_UE;
        $vat_rate3->rate = 0;

        $tax_report = new InvoiceTaxReport(['price_net' => 100, 'price_gross' => 155]);
        $tax_report->setRelation('vatRate', $vat_rate);

        $tax_report2 = new InvoiceTaxReport(['price_net' => 300, 'price_gross' => 500]);
        $tax_report2->setRelation('vatRate', $vat_rate2);

        $tax_report3 = new InvoiceTaxReport(['price_net' => 800, 'price_gross' => 1900]);
        $tax_report3->setRelation('vatRate', $vat_rate3);

        $invoice->setRelation('taxes', collect([$tax_report, $tax_report2, $tax_report3]));

        $filler = new TaxesFiller();

        $tax_items = $filler->calculate($invoice);

        $this->assertCount(3, $tax_items);

        /** @var TaxItem $tax_item */
        $tax_item = $tax_items[0];
        $this->assertTrue($tax_item instanceof TaxItem);
        $this->assertSame(23, $tax_item->getTaxRate());
        $this->assertSame(55, $tax_item->getVat());
        $this->assertSame(100, $tax_item->getNetPrice());
        $this->assertSame(0, $tax_item->getType());

        /** @var TaxItem $tax_item2 */
        $tax_item2 = $tax_items[1];
        $this->assertTrue($tax_item2 instanceof TaxItem);
        $this->assertSame(0, $tax_item2->getTaxRate());
        $this->assertSame(200, $tax_item2->getVat());
        $this->assertSame(300, $tax_item2->getNetPrice());
        $this->assertSame(4, $tax_item2->getType());

        /** @var TaxItem $tax_item3 */
        $tax_item3 = $tax_items[2];
        $this->assertTrue($tax_item3 instanceof TaxItem);
        $this->assertSame(0, $tax_item3->getTaxRate());
        $this->assertSame(1100, $tax_item3->getVat());
        $this->assertSame(800, $tax_item3->getNetPrice());
        $this->assertSame(4, $tax_item3->getType());
    }

    protected function verifyVat($net_price, $gross_price, $expected_vat)
    {
        $invoice = new Invoice();

        $vat_rate = new VatRate();
        $vat_rate->name = '23%';
        $vat_rate->rate = 23;

        $tax_report = new InvoiceTaxReport([
            'price_net' => $net_price,
            'price_gross' => $gross_price,
        ]);
        $tax_report->setRelation('vatRate', $vat_rate);

        $invoice->setRelation('taxes', collect([$tax_report]));

        $filler = new TaxesFiller();

        $tax_items = $filler->calculate($invoice);

        /** @var TaxItem $tax_item */
        $tax_item = $filler->calculate($invoice)[0];

        $this->assertSame($expected_vat, $tax_item->getVat());
    }

    protected function verifyForRate($rate_name, $rate, $expected_type)
    {
        $invoice = new Invoice();

        $vat_rate = new VatRate();
        $vat_rate->name = $rate_name;
        $vat_rate->rate = $rate;

        $tax_report = new InvoiceTaxReport(['price_net' => 100, 'price_gross' => 155]);
        $tax_report->setRelation('vatRate', $vat_rate);

        $invoice->setRelation('taxes', collect([$tax_report]));

        $filler = new TaxesFiller();

        $tax_items = $filler->calculate($invoice);

        $this->assertCount(1, $tax_items);

        /** @var TaxItem $tax_item */
        $tax_item = $tax_items[0];

        $this->assertTrue($tax_item instanceof TaxItem);
        $this->assertSame($rate, $tax_item->getTaxRate());
        $this->assertSame(55, $tax_item->getVat());
        $this->assertSame(100, $tax_item->getNetPrice());
        $this->assertSame($expected_type, $tax_item->getType());
    }
}
