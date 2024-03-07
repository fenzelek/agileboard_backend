<?php

namespace App\Policies;

use App\Models\Db\Payment;
use App\Models\Db\Subscription;
use App\Models\Db\Transaction;
use App\Models\Db\User;
use App\Models\Other\PaymentStatus;

class PaymentControllerPolicy extends BasePolicy
{
    protected $group = 'payment';

    public function show(User $user, Payment $payment)
    {
        try {
            //check is in company
            if (! $payment->transaction->companyModulesHistory()->where('company_id', $user->selectedCompany()->id)->first()) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function confirmBuy(User $user, Payment $payment)
    {
        try {
            if ($payment->status != PaymentStatus::STATUS_BEFORE_START) {
                return false;
            }

            //check is in company
            if (! $payment->transaction->companyModulesHistory()->where('company_id', $user->selectedCompany()->id)->first()) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function payAgain(User $user, Transaction $transaction)
    {
        try {
            //check has not canceled
            $notCanceled = $transaction->payments()
                ->where('status', '!=', PaymentStatus::STATUS_CANCELED)->first();

            if ($notCanceled) {
                return false;
            }

            //check is in company
            if (! $transaction->companyModulesHistory()->where('company_id', $user->selectedCompany()->id)->first()) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function cancelSubscription(User $user, Subscription $subscription)
    {
        try {
            if ($subscription->active == false) {
                return false;
            }

            //check is in company
            $transaction = $subscription->payments()->orderByDesc('id')->first()->transaction;
            if (! $transaction->companyModulesHistory()->where('company_id', $user->selectedCompany()->id)->first()) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function cancelPayment(User $user, Payment $payment)
    {
        try {
            if ($payment->status == PaymentStatus::STATUS_COMPLETED) {
                return false;
            }

            //check is in company
            if (! $payment->transaction->companyModulesHistory()->where('company_id', $user->selectedCompany()->id)->first()) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
