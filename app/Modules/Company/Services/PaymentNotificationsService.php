<?php

namespace App\Modules\Company\Services;

use App\Models\Db\CompanyModule;
use App\Models\Db\Payment;
use App\Modules\Company\Notifications\PaymentStatusInfo;
use App\Modules\Company\Notifications\RemindExpiring;
use App\Modules\Company\Notifications\RenewSubscriptionInformation;
use App\Modules\Company\Notifications\SubscriptionCanceled;

class PaymentNotificationsService
{
    /**
     * @param Payment $payment
     */
    public function paymentStatusInfo(Payment $payment)
    {
        $users = $payment->transaction->companyModulesHistory[0]->company->getOwners();
        foreach ($users as $user) {
            $user->user->notify(new PaymentStatusInfo($payment));
        }
    }

    /**
     * @param CompanyModule $companyModule
     */
    public function subscriptionCanceled(CompanyModule $companyModule)
    {
        $users = $companyModule->company->getOwners();
        foreach ($users as $user) {
            $user->user->notify(new SubscriptionCanceled($companyModule));
        }
    }

    /**
     * @param CompanyModule $companyModule
     */
    public function renewSubscriptionInformation(CompanyModule $companyModule)
    {
        foreach ([14, 1] as $day) {
            $modules = $companyModule->forNotifications($day, true)
                ->join('subscriptions', function ($join) {
                    $join->on('subscriptions.id', '=', 'subscription_id')
                        ->where('active', 1);
                })
                ->get();

            foreach ($modules as $module) {
                $module->subscription->user->notify(new RenewSubscriptionInformation($module, $day));
            }

            $modules = $companyModule->forNotifications($day, false)
                ->join('subscriptions', function ($join) {
                    $join->on('subscriptions.id', '=', 'subscription_id')
                        ->where('active', 1);
                })
                ->get();

            foreach ($modules as $module) {
                $module->subscription->user->notify(new RenewSubscriptionInformation($module, $day));
            }
        }
    }

    /**
     * @param CompanyModule $companyModule
     */
    public function remindExpiringPackages(CompanyModule $companyModule)
    {
        foreach ([30, 14, 7, 3, 2, 1] as $day) {
            $modules = $companyModule->forNotifications($day, true)
                ->join('subscriptions', function ($join) {
                    $join->on('subscriptions.id', '=', 'subscription_id')
                        ->where('active', 0);
                })
                ->get();

            $this->sendExpiring($modules, $day);

            $modules = $companyModule->forNotifications($day, true)
                ->leftJoin('subscriptions', 'subscriptions.id', '=', 'subscription_id')
                ->whereNull('subscriptions.id')
                ->get();

            $this->sendExpiring($modules, $day);
        }
    }

    /**
     * @param CompanyModule $companyModule
     */
    public function remindExpiringModules(CompanyModule $companyModule)
    {
        foreach ([30, 14, 7, 3, 2, 1] as $day) {
            $modules = $companyModule->forNotifications($day, false)
                ->join('subscriptions', function ($join) {
                    $join->on('subscriptions.id', '=', 'subscription_id')
                        ->where('active', 0);
                })
                ->get();

            $this->sendExpiring($modules, $day);

            $modules = $companyModule->forNotifications($day, false)
                ->leftJoin('subscriptions', 'subscriptions.id', '=', 'subscription_id')
                ->whereNull('subscriptions.id')
                ->get();

            $this->sendExpiring($modules, $day);
        }
    }

    /**
     * @param $modules
     * @param $day
     */
    private function sendExpiring($modules, $day)
    {
        foreach ($modules as $module) {
            $users = $module->company->getOwners();
            foreach ($users as $user) {
                $user->user->notify(new RemindExpiring($module, $day));
            }
        }
    }
}
