<?php

namespace App\Modules\SaleInvoice\Services\Builders;

use App\Interfaces\BuilderVat;

class Advance extends Vat implements BuilderVat
{
    /**
     * Set source document for invoice.
     */
    public function setDocument()
    {
        $this->payment->create($this->invoice, $this->user);
        $this->cashflow->addDuringIssuingInvoice($this->invoice, $this->user);
    }

    /**
     * Set parent document.
     */
    public function setParent()
    {
        $this->invoice->proforma_id = $this->request->input('proforma_id');
        $this->invoice->save();
        $this->invoice->nodeInvoices()->attach($this->request->input('proforma_id'));
    }

    /**
     * Creating special partial payment.
     */
    public function createSpecialPayment()
    {
    }

    /**
     * Updating or deleting special partial payment.
     */
    public function updateSpecialPayment()
    {
    }

    /**
     * Update payment and cashflow.
     */
    protected function updatePaymentAndCashflow()
    {
        $amount = normalize_price($this->request->input('price_gross'));
        if ($this->invoice->price_gross == $amount) {
            return;
        }
        $this->storePaymentAndCashflow($amount);
    }

    /**
     * Parse invoice item data from request.
     *
     * @param $item
     *
     * @return array
     */
    protected function parseItemData($item)
    {
        return array_merge(parent::parseItemData($item), [
            'proforma_item_id' => $item['proforma_item_id'],
        ]);
    }

    /**
     * Store payment an Cashflow for updated Invoice.
     *
     * @param $amount
     */
    protected function storePaymentAndCashflow($amount)
    {
        $this->payment->remove($this->invoice);
        $this->cashflow->out($this->invoice, $this->user);
        $payment_method = $this->payment_method::find($this->request->input('payment_method_id'));
        $this->payment->add($this->invoice, $this->user, $payment_method, $amount);
        $this->cashflow->in($this->invoice, $this->user, $payment_method, $amount);
        $this->invoice->payment_left = 0;
    }
}
