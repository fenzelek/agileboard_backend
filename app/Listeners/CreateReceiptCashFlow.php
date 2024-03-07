<?php

namespace App\Listeners;

use App\Events\Event;
use App\Models\Db\PaymentMethod;
use App\Models\Db\CashFlow;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\Receipt;
use Carbon\Carbon;

class CreateReceiptCashFlow
{
    /**
     * Handle the event.
     *
     * @param  Event $event
     *
     * @return void
     */
    public function handle(Event $event)
    {
        /** @var Receipt $user */
        $receipt = $event->receipt;

        if (PaymentMethod::paymentInAdvance($receipt->paymentMethod->id)) {
            $payment_method_types = collect($event->request->input('payment_method_types'));

            $total_incoming_cash = 0;

            $payment_method_types->each(function ($payment_method) use ($receipt, &$total_incoming_cash) {
                if ($receipt->paymentMethod->slug == PaymentMethodType::CASH_CARD
                    || $receipt->paymentMethod->slug == $payment_method['type']
                ) {
                    CashFlow::create([
                        'company_id' => auth()->user()->getSelectedCompanyId(),
                        'user_id' => auth()->user()->id,
                        'receipt_id' => $receipt->id,
                        'amount' => normalize_price($payment_method['amount']),
                        'direction' => CashFlow::DIRECTION_IN,
                        'flow_date' => Carbon::now()->toDateString(),
                        'cashless' => (int) ($payment_method['type'] == PaymentMethodType::DEBIT_CARD),
                    ]);

                    $total_incoming_cash += normalize_price($payment_method['amount']);
                }
            });

            if ($total_incoming_cash > $receipt->price_gross) {
                $cash_back = $total_incoming_cash - $receipt->price_gross;

                CashFlow::create([
                    'company_id' => auth()->user()->getSelectedCompanyId(),
                    'user_id' => auth()->user()->id,
                    'receipt_id' => $receipt->id,
                    'amount' => $cash_back,
                    'direction' => CashFlow::DIRECTION_OUT,
                    'flow_date' => Carbon::now()->toDateString(),
                ]);

                $receipt->cash_back = $cash_back;
                $receipt->save();
            }
        }
    }
}
