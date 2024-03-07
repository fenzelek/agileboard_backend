<?php

namespace App\Modules\SaleInvoice\Services;

use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\PaymentMethod;

class Billing
{
    /**
     * Check if payments should be modified. Only if price gross for payments in advance
     * or payment_id is changed.
     *
     * @param ModelInvoice $invoice
     * @param PaymentMethod $payment_method
     * @param $price_gross
     * @param bool $payment_in_advance
     * @param array|null $special_payment
     *
     * @return bool
     */
    public function shouldModified(
        ModelInvoice $invoice,
        PaymentMethod $payment_method,
        $price_gross,
        $payment_in_advance,
        $special_payment
    ): bool {
        $invoice_special_payments = $invoice->specialPayments;

        // there were no special payments but now there will be
        if (! count($invoice_special_payments) && ! empty($special_payment)) {
            return true;
        }

        // there were special payments and now there won't be any
        if (count($invoice_special_payments) && empty($special_payment)) {
            return true;
        }

        // there were special payments and now there will be too (but they might be modified)
        if (count($invoice_special_payments)) {
            return true;
        }

        return ($invoice->price_gross != $price_gross && $payment_in_advance) ||
            $invoice->payment_method_id != $payment_method->id;
    }

    /**
     * Check if billing of invoice completed.
     *
     * @param ModelInvoice $invoice
     *
     * @return bool
     */
    public function completed(ModelInvoice $invoice)
    {
        return $this->countPaymentLeft($invoice) <= 0;
    }

    /**
     * Count how much is left to be paid.
     *
     * @param ModelInvoice $invoice
     *
     * @return float
     */
    public function countPaymentLeft(ModelInvoice $invoice)
    {
        return $invoice->price_gross - $invoice->payments()->sum('amount');
    }
}
