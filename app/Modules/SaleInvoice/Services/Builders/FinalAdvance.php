<?php

namespace App\Modules\SaleInvoice\Services\Builders;

use App\Interfaces\BuilderVat;

class FinalAdvance extends Advance implements BuilderVat
{
    /**
     * Set source document for invoice.
     */
    public function setDocument()
    {
        $this->payment->addSupplement($this->invoice, $this->user);
        $this->cashflow->addSupplement($this->invoice, $this->user);
    }

    /**
     * Create Invoice Taxes
     * The Final Taxes store in Invoice Registry
     * The all Taxes store in FinalAdvance.
     */
    protected function createInvoiceTaxReport()
    {
        $this->storeInvoiceTaxes($this->request->input('taxes'), 'finalAdvanceTaxes');
        $this->storeInvoiceTaxes($this->request->input('advance_taxes'));
    }

    /**
     * Update payment and cashflow.
     */
    protected function updatePaymentAndCashflow()
    {
        $amount = normalize_price(collect($this->request->input('advance_taxes'))->sum('price_gross'));
        if (! empty($this->invoice->payments()->first()) && $this->invoice->payments()->first()->amount == $amount) {
            return;
        }
        $this->storePaymentAndCashflow($amount);
    }

    /**
     * Update taxes.
     */
    protected function updateTaxes()
    {
        $this->invoice->taxes()->delete();
        $this->invoice->finalAdvanceTaxes()->delete();
        $this->createInvoiceTaxReport();
    }

    /**
     * Update amounts and dates during updating invoice.
     */
    protected function updateAmountsAndDates()
    {
        parent::updateAmountsAndDates();
        $this->invoice->payment_left = 0;
    }
}
