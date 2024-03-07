<?php

namespace App\Modules\Company\Services\PayU;

use App\Models\Db\Payment;
use App\Models\Db\Subscription;
use App\Models\Db\User;
use App\Models\Other\PaymentStatus;
use App\Modules\Company\Services\PayU\Response\ResponseOrderByCardFirst;
use App\Modules\Company\Services\PayU\Response\ResponseOrderSimply;

class ResponseSaver
{
    private $payment;
    private $subscription;
    private $params;
    private $user;

    /**
     * ResponseSaver constructor.
     *
     * @param Payment $payment
     * @param Subscription $subscription
     * @param User $user
     * @param null $params
     */
    public function __construct(Payment $payment, Subscription $subscription, User $user, $params = null)
    {
        $this->payment = $payment;
        $this->subscription = $subscription;
        $this->user = $user;
        $this->params = $params;
    }

    /**
     * @param $response
     */
    public function save($response)
    {
        if ($response && ($response->isSuccess() || $response->getToken())) {
            $this->payment->status = PaymentStatus::STATUS_NEW;
            $this->payment->external_order_id = $response->getOrderId();

            if ($response instanceof ResponseOrderSimply) {
                $this->payment->type = $this->payment::TYPE_SIMPLE;
            } else {
                $this->payment->type = $this->payment::TYPE_CARD;
                if ($this->params && $this->params['subscription']) {
                    if ($response instanceof ResponseOrderByCardFirst) {
                        $this->saveSubscription($response->getToken());
                    } else {
                        $this->saveSubscription($this->params['token']);
                    }
                    $this->payment->subscription_id = $this->subscription->id;
                }
            }

            $this->payment->save();
        }
    }

    /**
     * @param $token
     */
    private function saveSubscription($token)
    {
        //get days
        $history = $this->payment->transaction->companyModulesHistory[0];
        $days = $history->moduleMod->modPrices()
            ->where('package_id', $history->package_id)
            ->where('module_mod_id', $history->module_mod_id)
            ->first()->days;
        $this->subscription = new $this->subscription();
        $this->subscription->days = $days;
        $this->subscription->repeats = 0;
        $this->subscription->card_token = encrypt($token);
        $this->subscription->user_id = $this->user->id;
        $this->subscription->save();
    }
}
