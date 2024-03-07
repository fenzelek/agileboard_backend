<?php

namespace App\Modules\Company\Jobs;

use App\Models\Db\CompanyModule;
use App\Modules\Company\Services\PaymentNotificationsService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RemindExpiringModules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $service;

    /**
     * RenewSubscriptionMails constructor.
     * @param PaymentNotificationsService $service
     */
    public function __construct(PaymentNotificationsService $service)
    {
        $this->service = $service;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->service->remindExpiringPackages(new CompanyModule());
            $this->service->remindExpiringModules(new CompanyModule());
        } catch (\Exception $exception) {
            \Log::error([get_class($exception), $exception->getMessage()]);
        }
    }
}
