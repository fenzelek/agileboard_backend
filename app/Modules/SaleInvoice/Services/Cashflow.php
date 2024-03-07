<?php

namespace App\Modules\SaleInvoice\Services;

use App\Models\Db\User;
use App\Models\Other\PaymentMethodType;
use Carbon\Carbon;
use App\Models\Db\CashFlow as ModelCashFlow;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\PaymentMethod;

class Cashflow
{
    /**
     * @var PaymentMethod
     */
    protected $payment_method;

    /**
     * @var ModelCashFlow
     */
    protected $cashflow;

    /**
     * Cashflow constructor.
     *
     * @param PaymentMethod $payment_method
     * @param ModelCashFlow $cashflow
     */
    public function __construct(PaymentMethod $payment_method, ModelCashFlow $cashflow)
    {
        $this->payment_method = $payment_method;
        $this->cashflow = $cashflow;
    }

    /**
     * Add cashflow IN/OUT for invoice
     * Price_gross have to be normalize.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     * @param $price_gross
     * @param $cashless
     * @param string $direction
     * @param string $flow_date
     */
    public function add(
        ModelInvoice $invoice,
        User $user,
        $price_gross,
        $cashless,
        $direction = ModelCashFlow::DIRECTION_IN,
        $flow_date = null
    ) {
        if (empty($flow_date)) {
            $flow_date = Carbon::now()->toDateString();
        }
        $this->cashflow->create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company->id,
            'user_id' => $user->id,
            'amount' => $price_gross,
            'direction' => $direction,
            'cashless' => $cashless,
            'flow_date' => $flow_date,
        ]);
    }

    /**
     * Init cashflow out by cashflow in.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     * @param int $cashflow_in
     * @param PaymentMethod $payment_method
     */
    public function initOutByIn(
        ModelInvoice $invoice,
        User $user,
        $cashflow_in,
        PaymentMethod $payment_method
    ) {
        $this->add(
            $invoice,
            $user,
            $cashflow_in,
            $this->isCashless($payment_method),
            ModelCashFlow::DIRECTION_OUT
        );
    }

    /**
     * Return cash flow in if there is more then cash flow out for a given invoice.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     *
     * @return mixed
     */
    public function cashflowInOverCashflowOut(ModelInvoice $invoice, User $user)
    {
        $sumCashFlowIn = $this->cashflow->inCompany($user)
            ->where('invoice_id', $invoice->id)
            ->where('direction', ModelCashFlow::DIRECTION_IN)
            ->sum('amount');
        $sumCashFlowOut = $this->cashflow->inCompany($user)
            ->where('invoice_id', $invoice->id)
            ->where('direction', ModelCashFlow::DIRECTION_OUT)
            ->sum('amount');

        if ($sumCashFlowIn - $sumCashFlowOut) {
            return $sumCashFlowIn - $sumCashFlowOut;
        }

        return false;
    }

    /**
     * Add cashflow in.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     * @param PaymentMethod $payment_method
     * @param $amount
     */
    public function in(ModelInvoice $invoice, User $user, PaymentMethod $payment_method, $amount)
    {
        $cashless = $this->isCashless($payment_method);

        $this->add(
            $invoice,
            $user,
            $amount,
            $cashless,
            ModelCashFlow::DIRECTION_IN
        );
    }

    /**
     * Add cash flow for new invoice.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     */
    public function addDuringIssuingInvoice(ModelInvoice $invoice, User $user)
    {
        $this->addForIssuingInvoice($invoice, $user);
    }

    /**
     * Add supplement cash flow for final advance invoice.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     */
    public function addSupplement(ModelInvoice $invoice, User $user)
    {
        $amount = $invoice->taxes()->sum('price_gross');
        $this->addForIssuingInvoice($invoice, $user, $amount);
    }

    /**
     * Add Cashflow out.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     */
    public function out(ModelInvoice $invoice, User $user)
    {
        $invoice_cash_flow_in = $this->cashflowInOverCashflowOut($invoice, $user);
        if ($invoice_cash_flow_in) {
            $this->initOutByIn($invoice, $user, $invoice_cash_flow_in, $invoice->paymentMethod);
        }
    }

    /**
     * Check that transaction is cashless.
     * @param PaymentMethod|string $payment_method
     * @return bool
     */
    protected function isCashless($payment_method): bool
    {
        if ($payment_method instanceof PaymentMethod) {
            return $payment_method->slug == PaymentMethodType::CASH ? false : true;
        }

        return (bool) $payment_method;
    }

    /**
     * @param ModelInvoice $invoice
     * @param User $user
     * @param $amount
     */
    protected function addForIssuingInvoice(ModelInvoice $invoice, User $user, $amount = null)
    {
        $amount = $amount ?? $invoice->price_gross;
        $direction = ($amount > 0) ? ModelCashFlow::DIRECTION_IN : ModelCashFlow::DIRECTION_OUT;
        $this->add(
            $invoice,
            $user,
            abs($amount),
            $this->isCashless($invoice->paymentMethod),
            $direction,
            $invoice->issue_date
        );
    }
}
