<?php

namespace App\Modules\Company\Jobs;

use App\Models\Db\CompanyModule;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Company\Services\PaymentNotificationsService;
use App\Modules\Company\Services\PaymentService;
use App\Modules\Company\Services\PayU\ParamsFactory;
use App\Modules\Company\Services\PayU\PayU;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RenewSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $service;
    private $paramsFactory;
    private $notificationsService;
    private $updater;

    /**
     * RenewSubscription constructor.
     * @param PaymentService $service
     * @param PaymentNotificationsService $notificationsService
     * @param ParamsFactory $paramsFactory
     * @param CompanyModuleUpdater $updater
     */
    public function __construct(PaymentService $service, PaymentNotificationsService $notificationsService, ParamsFactory $paramsFactory, CompanyModuleUpdater $updater)
    {
        $this->service = $service;
        $this->paramsFactory = $paramsFactory;
        $this->notificationsService = $notificationsService;
        $this->updater = $updater;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->service->renewSubscription(
                new CompanyModule(),
                $this->paramsFactory,
                app()->make(PayU::class),
                $this->notificationsService,
                $this->updater
            );
        } catch (\Exception $exception) {
            \Log::error([get_class($exception), $exception->getMessage()]);
        }
    }
}
