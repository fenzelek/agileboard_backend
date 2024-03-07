<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\FieldFiller;

use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceReverseCharge;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceReverseChargeType;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use Tests\TestCase;

class GetDeductionValueTest extends TestCase
{
    /** @test */
    public function it_returns_1_for_vat_in_country_invoice()
    {
        $invoice = new Invoice();

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
        ]);
        $invoice_contractor->setRelation('vatinPrefix', null);
        $invoice_type = new InvoiceType(['slug' => InvoiceTypeStatus::VAT]);

        $invoice->setRelation('invoiceContractor', $invoice_contractor);
        $invoice->setRelation('invoiceType', $invoice_type);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(1, $field_filter->getDeductionValue(0));
    }

    /** @test */
    public function it_returns_65_for_vat_in_eu_invoice()
    {
        $invoice = new Invoice();

        $vatin_prefix = new CountryVatinPrefix(['key' => 'HU']);

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
        ]);
        $invoice_contractor->setRelation('vatinPrefix', $vatin_prefix);

        $invoice_type = new InvoiceType(['slug' => InvoiceTypeStatus::VAT]);

        $invoice->setRelation('invoiceContractor', $invoice_contractor);
        $invoice->setRelation('invoiceType', $invoice_type);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(65, $field_filter->getDeductionValue(0));
    }

    /** @test */
    public function it_returns_97_for_vat_in_eu_invoice_with_export_3()
    {
        $invoice = new Invoice();

        $vatin_prefix = new CountryVatinPrefix(['key' => 'HU']);

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
        ]);
        $invoice_contractor->setRelation('vatinPrefix', $vatin_prefix);

        $invoice_type = new InvoiceType(['slug' => InvoiceTypeStatus::VAT]);

        $invoice->setRelation('invoiceContractor', $invoice_contractor);
        $invoice->setRelation('invoiceType', $invoice_type);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(97, $field_filter->getDeductionValue(3));
    }

    /** @test */
    public function it_returns_97_for_vat_in_eu_invoice_with_export_1()
    {
        $invoice = new Invoice();

        $vatin_prefix = new CountryVatinPrefix(['key' => 'HU']);

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
        ]);
        $invoice_contractor->setRelation('vatinPrefix', $vatin_prefix);

        $invoice_type = new InvoiceType(['slug' => InvoiceTypeStatus::VAT]);

        $invoice->setRelation('invoiceContractor', $invoice_contractor);
        $invoice->setRelation('invoiceType', $invoice_type);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(97, $field_filter->getDeductionValue(1));
    }

    /** @test */
    public function it_returns_33_for_vat_outside_eu_invoice_with_export_1()
    {
        $invoice = new Invoice();

        $vatin_prefix = new CountryVatinPrefix(['key' => 'AF']);

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
        ]);
        $invoice_contractor->setRelation('vatinPrefix', $vatin_prefix);

        $invoice_type = new InvoiceType(['slug' => InvoiceTypeStatus::VAT]);

        $invoice->setRelation('invoiceContractor', $invoice_contractor);
        $invoice->setRelation('invoiceType', $invoice_type);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(33, $field_filter->getDeductionValue(1));
    }

    /** @test */
    public function it_returns_33_for_vat_outside_eu_invoice_with_export_3()
    {
        $invoice = new Invoice();

        $vatin_prefix = new CountryVatinPrefix(['key' => 'AF']);

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
        ]);
        $invoice_contractor->setRelation('vatinPrefix', $vatin_prefix);

        $invoice_type = new InvoiceType(['slug' => InvoiceTypeStatus::VAT]);

        $invoice->setRelation('invoiceContractor', $invoice_contractor);
        $invoice->setRelation('invoiceType', $invoice_type);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame(33, $field_filter->getDeductionValue(3));
    }

    /** @test */
    public function it_returns_129_for_reverse_charge_inside_country_with_customer_tax()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            0,
            InvoiceReverseChargeType::CUSTOMER_TAX,
            129
        );
    }

    /** @test */
    public function it_returns_1_for_reverse_charge_inside_country_with_in()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            0,
            InvoiceReverseChargeType::IN,
            1
        );
    }

    /** @test */
    public function it_returns_1_for_reverse_charge_inside_country_with_out_ue()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            0,
            InvoiceReverseChargeType::OUT_EU,
            1
        );
    }

    /** @test */
    public function it_returns_1_for_reverse_charge_inside_country_with_out_ue_tax_back()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            0,
            InvoiceReverseChargeType::OUT_EU_TAX_BACK,
            1
        );
    }

    /** @test */
    public function it_returns_1_for_reverse_charge_inside_country_with_in_ue()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            0,
            InvoiceReverseChargeType::IN_UE,
            1
        );
    }

    /** @test */
    public function it_returns_1_for_reverse_charge_inside_country_with_in_eu_triple()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            0,
            InvoiceReverseChargeType::IN_EU_TRIPLE,
            1
        );
    }

    /** @test */
    public function it_returns_1_for_reverse_charge_inside_country_with_out()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            0,
            InvoiceReverseChargeType::OUT,
            1
        );
    }

    /** @test */
    public function it_returns_1_for_reverse_charge_inside_country_with_out_np()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            0,
            InvoiceReverseChargeType::OUT_NP,
            1
        );
    }

    /** @test */
    public function it_returns_1_for_reverse_charge_inside_country_with_in_eu_customer_tax()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            0,
            InvoiceReverseChargeType::IN_EU_CUSTOMER_TAX,
            1
        );
    }

    /** @test */
    public function it_returns_1_for_reverse_charge_inside_country_with_out_eu_customer_tax()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            0,
            InvoiceReverseChargeType::OUT_EU_CUSTOMER_TAX,
            1
        );
    }

    /** @test */
    public function it_returns_33_for_reverse_charge_inside_ue_with_customer_tax()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            3,
            InvoiceReverseChargeType::CUSTOMER_TAX,
            33
        );
    }

    /** @test */
    public function it_returns_33_for_reverse_charge_inside_ue_with_in()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            3,
            InvoiceReverseChargeType::IN,
            33
        );
    }

    /** @test */
    public function it_returns_33_for_reverse_charge_inside_ue_with_out_ue()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            3,
            InvoiceReverseChargeType::OUT_EU,
            33
        );
    }

    /** @test */
    public function it_returns_33_for_reverse_charge_inside_ue_with_out_ue_tax_back()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            3,
            InvoiceReverseChargeType::OUT_EU_TAX_BACK,
            33
        );
    }

    /** @test */
    public function it_returns_33_for_reverse_charge_inside_ue_with_in_ue()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            3,
            InvoiceReverseChargeType::IN_UE,
            33
        );
    }

    /** @test */
    public function it_returns_161_for_reverse_charge_inside_ue_with_in_eu_triple()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            3,
            InvoiceReverseChargeType::IN_EU_TRIPLE,
            161
        );
    }

    /** @test */
    public function it_returns_33_for_reverse_charge_inside_ue_with_out()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            3,
            InvoiceReverseChargeType::OUT,
            33
        );
    }

    /** @test */
    public function it_returns_33_for_reverse_charge_inside_ue_with_out_np()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            3,
            InvoiceReverseChargeType::OUT_NP,
            33
        );
    }

    /** @test */
    public function it_returns_33_for_reverse_charge_inside_ue_with_in_eu_customer_tax()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            3,
            InvoiceReverseChargeType::IN_EU_CUSTOMER_TAX,
            33
        );
    }

    /** @test */
    public function it_returns_33_for_reverse_charge_inside_ue_with_out_eu_customer_tax()
    {
        $this->verifyForExportAndGivenReserveChargeType(
            3,
            InvoiceReverseChargeType::OUT_EU_CUSTOMER_TAX,
            33
        );
    }

    protected function verifyForExportAndGivenReserveChargeType($export, $reverse_charge_type, $expected)
    {
        $invoice = new Invoice();

        $vatin_prefix = new CountryVatinPrefix(['key' => 'AF']);

        $invoice_contractor = new InvoiceContractor([
            'vatin' => '123456789',
        ]);
        $invoice_contractor->setRelation('vatinPrefix', $vatin_prefix);

        $invoice_type = new InvoiceType(['slug' => InvoiceTypeStatus::REVERSE_CHARGE]);
        $invoice_reverse_charge =
            new InvoiceReverseCharge(['slug' => $reverse_charge_type]);

        $invoice->setRelation('invoiceContractor', $invoice_contractor);
        $invoice->setRelation('invoiceType', $invoice_type);
        $invoice->setRelation('invoiceReverseCharge', $invoice_reverse_charge);

        $field_filter = new FieldFiller();
        $field_filter->setInvoice($invoice);

        $this->assertSame($expected, $field_filter->getDeductionValue($export));
    }
}
