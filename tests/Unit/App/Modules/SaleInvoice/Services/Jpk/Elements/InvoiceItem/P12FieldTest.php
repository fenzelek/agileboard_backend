<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;

use App\Models\Db\VatRate;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class P12FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_23()
    {
        $this->verifyForRate(23, VatRate::TAX_23, '23');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_8()
    {
        $this->verifyForRate(8, VatRate::TAX_8, '8');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_5()
    {
        $this->verifyForRate(5, VatRate::TAX_5, '5');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_0()
    {
        $this->verifyForRate(0, VatRate::TAX_0, '');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_0_wdt()
    {
        $this->verifyForRate(0, '0% WDT', '');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_0_exp()
    {
        $this->verifyForRate(0, '0% EXP', '');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_np_ue()
    {
        $this->verifyForRate(0, VatRate::NP_UE, '');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_np()
    {
        $this->verifyForRate(0, VatRate::NP, '');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_zw()
    {
        $this->verifyForRate(0, VatRate::ZW, 'zw');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_22()
    {
        $this->verifyForRate(22, VatRate::TAX_22, '22');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_7()
    {
        $this->verifyForRate(7, VatRate::TAX_7, '7');
    }

    /** @test */
    public function it_returns_valid_vat_rate_when_vat_rate_is_3()
    {
        $this->verifyForRate(3, '3%', '3');
    }

    protected function verifyForRate($rate_value, $rate_slug, $expected_value)
    {
        $invoice_item_model = $this->getDefaultInvoiceItemModel();

        $invoice_item_model->fill(['vat_rate' => 'anything']);

        $vat_rate = new VatRate();
        $vat_rate->slug = $rate_slug;
        $vat_rate->rate = $rate_value;

        $invoice_item_model->setRelation('vatRate', $vat_rate);
        $invoice = new InvoiceModel();

        $invoice_item = new InvoiceItem($this->mockInvoiceItemHelper());
        $result = $invoice_item->create($invoice_item_model, $invoice);

        $this->findAndVerifyField($result, 'tns:P_12', $expected_value);
    }
}
