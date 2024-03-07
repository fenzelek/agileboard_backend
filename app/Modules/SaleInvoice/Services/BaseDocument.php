<?php

namespace App\Modules\SaleInvoice\Services;

use App\Models\Db\OnlineSale;
use App\Models\Db\PaymentMethod;
use App\Models\Db\Receipt;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\User;
use App\Models\Other\PaymentMethodType;

class BaseDocument
{
    /**
     * @var Receipt
     */
    protected $receipt;

    /**
     * @var OnlineSale
     */
    protected $online_sale;

    /**
     * @var PaymentMethod
     */
    protected $payment_method;

    /**
     * @var Payment
     */
    protected $payment;

    /**
     * BaseDocument constructor.
     *
     * @param Receipt $receipt
     * @param OnlineSale $online_sale
     * @param PaymentMethod $payment_method
     * @param Payment $payment
     */
    public function __construct(Receipt $receipt, OnlineSale $online_sale, PaymentMethod $payment_method, Payment $payment)
    {
        $this->receipt = $receipt;
        $this->online_sale = $online_sale;
        $this->payment_method = $payment_method;
        $this->payment = $payment;
    }

    /**
     * Verify whether invoice is being created from other document.
     *
     * @param string $document_type
     *
     * @return bool
     */
    public function creatingFromBaseDocument($document_type)
    {
        if (in_array($document_type, ['receipts', 'online_sales'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Set existing invoice as created from other base document.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     * @param string $document_type
     * @param int $document_id
     */
    public function setFromBaseDocument(ModelInvoice $invoice, User $user, $document_type, $document_id)
    {
        $mark_invoice_as_paid = false;

        // find corresponding base document, attach it to invoice and decide whether invoice should
        // be marked as paid
        if ($document_type == 'receipts') {
            $base_document = $this->receipt->find($document_id);
            $invoice->receipts()->attach($document_id);
            if (count($base_document) > 1 ||
                in_array(
                    $base_document[0]->paymentMethod->slug,
                    [PaymentMethodType::CASH, PaymentMethodType::DEBIT_CARD, PaymentMethodType::CASH_CARD]
                )
            ) {
                $mark_invoice_as_paid = true;
            }
        } else {
            $base_document = $this->online_sale->find($document_id);
            $invoice->onlineSales()->attach($document_id);
            $mark_invoice_as_paid = true;
        }

        if ($mark_invoice_as_paid) {
            $this->payment->create($invoice, $user, $base_document);
        }
    }

    /**
     * Check if invoice was not created from any base document.
     *
     * @param ModelInvoice $invoice
     * @return bool
     */
    public function notBelongs(ModelInvoice $invoice): bool
    {
        return ! $invoice->receipts()->count() && ! $invoice->onlineSales()->count();
    }
}
