<?php

namespace App\Modules\SaleInvoice\Services;

use App\Models\Db\PaymentMethod;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class Payment
{
    /**
     * @var PaymentMethod
     */
    protected $payment_method;

    /**
     * Payment constructor.
     *
     * @param PaymentMethod $payment_method
     */
    public function __construct(PaymentMethod $payment_method)
    {
        $this->payment_method = $payment_method;
    }

    /**
     * @param $payment_method
     * @return bool
     */
    public function paymentInAdvance($payment_method_id): bool
    {
        return $this->payment_method->paymentInAdvance($payment_method_id);
    }

    /**
     * Remove invoice payments and reset paid flag.
     *
     * @param ModelInvoice $invoice
     */
    public function remove(ModelInvoice $invoice)
    {
        $invoice->payments()->delete();
        $invoice->paid_at = null;
    }

    /**
     * Add payment for invoice.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     * @param PaymentMethod $payment_method
     * @param $price_gross
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function add(
        ModelInvoice $invoice,
        User $user,
        PaymentMethod $payment_method,
        $price_gross
    ) {
        return $invoice->payments()->create([
            'amount' => $price_gross,
            'payment_method_id' => $payment_method->id,
            'registrar_id' => $user->id,
        ]);
    }

    /**
     * Create new invoice payment.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     * @param Collection|null $base_documents
     */
    public function create(ModelInvoice $invoice, User $user, Collection $base_documents = null)
    {
        $this->setInInvoice($invoice, $base_documents);
        $this->add($invoice, $user, $invoice->paymentMethod, $invoice->price_gross);
    }

    public function getMethod($payment_method_id)
    {
        return $this->payment_method->find($payment_method_id);
    }

    /**
     * Add Supplement for Final Advance Invoice.
     * @param ModelInvoice $invoice
     * @param User $user
     */
    public function addSupplement(ModelInvoice $invoice, User $user)
    {
        $amount = $invoice->taxes()->sum('price_gross');
        $this->setInInvoice($invoice);
        $this->add($invoice, $user, $invoice->paymentMethod, $amount);
    }

    /**
     * @param ModelInvoice $invoice
     * @param Collection $base_documents
     */
    protected function setInInvoice(ModelInvoice $invoice, Collection $base_documents = null)
    {
        if (null === $base_documents) {
            $invoice->paid_at = Carbon::parse($invoice->issue_date)
                ->addDays($invoice->payment_term_days)
                ->toDateString();
        } else {
            // For collective invoice set paid_at same as issue date
            if ($base_documents->count() > 1) {
                $invoice->paid_at = $invoice->issue_date;
            } else {
                $invoice->paid_at = $base_documents[0]->sale_date;
            }
        }
        $invoice->payment_left = 0;
        $invoice->save();
    }
}
