<?php

namespace Tests\Unit\App\Models\Db\Invoice;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceReverseCharge;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceReverseChargeType;
use App\Models\Other\InvoiceTypeStatus;
use Tests\TestCase;

class IsEuTripleReverseChargeTest extends TestCase
{
    /** @test */
    public function it_returns_true_for_reverse_charge_in_eu_triple()
    {
        $invoice = new Invoice();
        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::REVERSE_CHARGE])
        );
        $invoice->setRelation(
            'invoiceReverseCharge',
            new InvoiceReverseCharge(['slug' => InvoiceReverseChargeType::IN_EU_TRIPLE])
        );

        $this->assertTrue($invoice->isEuTripleReverseCharge());
    }

    /** @test */
    public function it_returns_true_for_reverse_charge_correction_in_eu_triple()
    {
        $invoice = new Invoice();
        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION])
        );
        $invoice->setRelation(
            'invoiceReverseCharge',
            new InvoiceReverseCharge(['slug' => InvoiceReverseChargeType::IN_EU_TRIPLE])
        );

        $this->assertTrue($invoice->isEuTripleReverseCharge());
    }

    /** @test */
    public function it_returns_false_for_reverse_charge_in()
    {
        $this->verifyIsNotEuTriple(InvoiceReverseChargeType::IN);
    }

    /** @test */
    public function it_returns_false_for_reverse_charge_out_eu()
    {
        $this->verifyIsNotEuTriple(InvoiceReverseChargeType::OUT_EU);
    }

    /** @test */
    public function it_returns_false_for_reverse_charge_out_eu_tax_back()
    {
        $this->verifyIsNotEuTriple(InvoiceReverseChargeType::OUT_EU_TAX_BACK);
    }

    /** @test */
    public function it_returns_false_for_reverse_charge_in_ue_tax_back()
    {
        $this->verifyIsNotEuTriple(InvoiceReverseChargeType::IN_UE);
    }

    /** @test */
    public function it_returns_false_for_reverse_charge_customer_tax_back()
    {
        $this->verifyIsNotEuTriple(InvoiceReverseChargeType::CUSTOMER_TAX);
    }

    /** @test */
    public function it_returns_false_for_reverse_charge_out_tax_back()
    {
        $this->verifyIsNotEuTriple(InvoiceReverseChargeType::OUT);
    }

    /** @test */
    public function it_returns_false_for_reverse_charge_out_np_tax_back()
    {
        $this->verifyIsNotEuTriple(InvoiceReverseChargeType::OUT_NP);
    }

    /** @test */
    public function it_returns_false_for_reverse_charge_in_eu_customer_tax()
    {
        $this->verifyIsNotEuTriple(InvoiceReverseChargeType::IN_EU_CUSTOMER_TAX);
    }

    /** @test */
    public function it_returns_false_for_vat()
    {
        $this->verifyIsNotEuTriple(InvoiceReverseChargeType::IN_EU_CUSTOMER_TAX, InvoiceTypeStatus::VAT);
    }

    protected function verifyIsNotEuTriple($reverse_charge_type, $invoice_type = InvoiceTypeStatus::REVERSE_CHARGE)
    {
        $invoice = new Invoice();
        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => $invoice_type])
        );
        $invoice->setRelation(
            'invoiceReverseCharge',
            new InvoiceReverseCharge(['slug' => $reverse_charge_type])
        );

        $this->assertFalse($invoice->isEuTripleReverseCharge());
    }
}
