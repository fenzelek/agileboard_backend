<?php

namespace App\Modules\Company\Services;

use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\ModPrice;
use App\Models\Db\Module;
use DB;
use App\Models\Db\CompanyModule;
use App\Models\Db\Subscription;
use App\Models\Db\Transaction;
use App\Models\Db\User;
use App\Modules\Company\Services\PayU\ParamsFactory;
use App\Modules\Company\Services\PayU\ResponseSaver;
use App\Models\Other\PaymentStatus;
use App\Models\Db\Payment;
use App\Modules\Company\Services\PayU\PayU;
use Carbon\Carbon;

class PaymentService
{
    /**
     * Prepare payments.
     *
     * @param Payment $payment
     * @param $transaction_id
     * @param $price_total
     * @param $vat
     * @param $currency
     * @param $days
     * @param null $subscription_id
     * @param Carbon|null $expiration_date
     * @return mixed
     */
    public function preparePayments(
        Payment $payment,
        $transaction_id,
        $price_total,
        $vat,
        $currency,
        $days,
        $subscription_id = null,
        Carbon $expiration_date = null
    ) {
        return $payment->create([
            'price_total' => $price_total,
            'vat' => $vat,
            'currency' => $currency,
            'status' => PaymentStatus::STATUS_BEFORE_START,
            'days' => $days,
            'transaction_id' => $transaction_id,
            'subscription_id' => $subscription_id,
            'expiration_date' => $expiration_date,
        ]);
    }

    /**
     * @param PayU $payU
     * @param User $user
     * @param Payment $payment
     * @param Subscription $subscription
     * @param array $params
     * @return PayU\Response\ResponseOrderByCardFirst|PayU\Response\ResponseOrderByCardNext|PayU\Response\ResponseOrderSimply|bool
     * @throws \Exception
     */
    public function proceed(PayU $payU, User $user, Payment $payment, Subscription $subscription, $params = null)
    {
        $saver = new ResponseSaver($payment, $subscription, $user, $params);
        $payU->setUser($user);
        $response = $payU->createOrder($saver);

        return $response;
    }

    /**
     * @param Transaction $transaction
     * @return mixed
     */
    public function payAgain(Transaction $transaction)
    {
        $payment = $transaction->payments()->orderByDesc('id')->first();

        return $this->preparePayments(
            $payment->getModel(),
            $transaction->id,
            $payment->price_total,
            $payment->vat,
            $payment->currency,
            $payment->subscription_id,
            $payment->expiration_date
        );
    }

    /**
     * @param Payment $payment
     * @param PaymentNotificationsService $notification
     * @param CompanyModuleUpdater $updater
     * @param CompanyModuleHistory $history
     * @param CompanyModule $companyModule
     */
    public function paymentCompleted(
        Payment $payment,
        PaymentNotificationsService $notification,
        CompanyModuleUpdater $updater,
        CompanyModuleHistory $history,
        CompanyModule $companyModule
    ) {
        if ($payment->subscription) {
            $payment->subscription->repeats = 0;
            $payment->subscription->save();
        }

        $notification->paymentStatusInfo($payment);

        //expiration time - security when changing now
        if ($payment->expiration_date && Carbon::now()->gte($payment->expiration_date)) {
            $payment->status = PaymentStatus::STATUS_COMPLETED_BUT_NOT_USED;
            $payment->save();

            return;
        }

        //active subscription
        if ($payment->subscription_id) {
            $payment->subscription()->update(['active' => 1]);
        }

        //activation modules
        $history = $history->where('transaction_id', $payment->transaction_id)->first();
        $module = $companyModule->where('company_id', $history->company_id)
            ->where('module_id', $history->module_id)->first();

        $days = $payment->days;

        if ($module->expiration_date && Carbon::now()->lt($module->expiration_date) && ! $history->start_date) {
            //change
            $days = 0;
        } else {
            if (null === $module->package_id && $days > $history->company->packageExpirationInDays()) {
                //first buy external modules - all interval
                $days = $history->company->packageExpirationInDays();
            }
        }

        $updater->setCompany($history->company);
        $updater->activateWithUpdateHistory($payment->transaction_id, $days, $history->start_date);
    }

    /**
     * @param CompanyModule $companyModule
     * @param ParamsFactory $paramsFactory
     * @param PayU $payU
     * @param PaymentNotificationsService $notificationsService
     * @param CompanyModuleUpdater $updater
     */
    public function renewSubscription(CompanyModule $companyModule, ParamsFactory $paramsFactory, PayU $payU, PaymentNotificationsService $notificationsService, CompanyModuleUpdater $updater)
    {
        //packages
        $modules = $companyModule
            ->where('expiration_date', '<', Carbon::now())
            ->whereHas('subscription', function ($q) {
                $q->where('active', true);
            })
            ->whereNotNull('package_id')
            ->groupBy('company_id')
            ->get();

        $this->doRenew($modules, $paramsFactory, $payU, $notificationsService, $updater);

        //modules
        $modules = $companyModule
            ->where('expiration_date', '<', Carbon::now())
            ->whereHas('subscription', function ($q) {
                $q->where('active', true);
            })
            ->whereNull('package_id')
            ->get();

        $this->doRenew($modules, $paramsFactory, $payU, $notificationsService, $updater);
    }

    /**
     * @param Subscription $subscription
     * @param CompanyModuleUpdater|null $updater
     */
    public function cancelSubscription(Subscription $subscription, CompanyModuleUpdater $updater = null)
    {
        $subscription->active = false;
        $subscription->save();

        //cancel all subscription if cancel package
        $history = $subscription->payments()->first()->transaction->companyModulesHistory()->first();
        if ($history->package_id) {
            $modules = $history->company->companyModules()
                ->whereNull('package_id')->wherenotNull('subscription_id')->get();

            foreach ($modules as $module) {
                $module->subscription->active = false;
                $module->subscription->save();

                if ($updater) {
                    $updater->changeToDefault($module);
                }
            }
        }
    }

    /**
     * @param $modules
     * @param $paramsFactory
     * @param $payU
     * @param $notificationsService
     * @param $updater
     */
    private function doRenew($modules, $paramsFactory, $payU, $notificationsService, $updater)
    {
        foreach ($modules as $module) {
            $updater->setCompany($module->company);
            //14 days
            if ($module->subscription->repeats == 14) {
                $this->cancelSubscription($module->subscription, $updater);
                $notificationsService->subscriptionCanceled($module);
                $updater->changeToDefault($module);
            } else {
                $module->subscription->repeats += 1;
                $module->subscription->save();

                if ($module->package_id || $module->company->packageExpirationInDays()) {
                    $this->createPaymentForSubscription($module, $paramsFactory, $payU, $updater);
                }
            }
        }
    }

    /**
     * @param CompanyModule $module
     * @param ParamsFactory $paramsFactory
     * @param PayU $payU
     * @param CompanyModuleUpdater $updater
     */
    private function createPaymentForSubscription(CompanyModule $module, ParamsFactory $paramsFactory, PayU $payU, CompanyModuleUpdater $updater)
    {
        $payment = $module->subscription->payments()->orderByDesc('id')->first();

        if ($module->subscription->repeats == 1 || $payment->status == PaymentStatus::STATUS_CANCELED) {
            DB::transaction(function () use ($module, $paramsFactory, $payU, $payment, $updater) {

                //create history
                $transaction = $updater->createHistory($this->getModules($module));

                $price = $payment->price_total;
                $vat = $payment->vat;
                $days = $module->subscription->days;

                if (! $module->package_id) {
                    $price = ModPrice::where('module_mod_id', $module->companyModuleHistory->module_mod_id)
                        ->where('days', $days)->first()->price;

                    if ($module->company->packageExpirationInDays() < $module->subscription->days) {
                        $days = $module->company->packageExpirationInDays();
                        $price = (int) ceil($price / $module->subscription->days * $days);
                    }
                    $vat = (int) ceil($price * 23 / 123);
                }
                //create payment
                $payment = $this->preparePayments(
                    $payment->getModel(),
                    $transaction->id,
                    $price,
                    $vat,
                    $payment->currency,
                    $days,
                    $module->subscription->id
                );

                //order params
                $orderParams = $paramsFactory->createOrderParams(
                    [
                        'token' => decrypt($module->subscription->card_token),
                        'type' => Payment::TYPE_CARD,
                    ],
                    $module->company->getOwners()->first()->user,
                    $payment
                );

                $payU->setParams($orderParams);

                //run order
                $this->proceed($payU, $module->subscription->user, $payment, $module->subscription);
            });
        }
    }

    private function getModules(CompanyModule $company_module)
    {
        $days = $company_module->subscription->days;

        if ($company_module->package_id) {
            $company_modules = CompanyModule::where('company_id', $company_module->company_id)
                ->whereNotNull('package_id')->get();
        } else {
            $company_modules = collect([$company_module]);
        }

        $modules = collect([]);
        foreach ($company_modules as $item) {
            $history = $item->companyModuleHistory()
                ->where('status', CompanyModuleHistory::STATUS_USED)
                ->where('company_id', $item->company_id)->first();

            //@todo PAYMENT SYSTEM check this later, get module from package (package null) not from history
            $modules [] = $history->module()
                ->with(['mods' => function ($q) use ($history, $days) {
                    $q->where('id', $history->module_mod_id);
                    $q->with([
                        'modPrices' => function ($q) use ($history, $days) {
                            $q->where('module_mod_id', $history->module_mod_id);
                            $q->where('package_id', $history->package_id);
                            $q->where('days', $days);
                        },
                    ]);
                }])
                ->first();
        }

        return $modules;
    }
}
