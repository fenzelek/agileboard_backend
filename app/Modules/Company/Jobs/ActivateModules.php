<?php

namespace App\Modules\Company\Jobs;

use App\Models\Db\CompanyModuleHistory;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Company\Services\Payments\ModuleService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ActivateModules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $updater;
    private $service;

    /**
     * ActivateModules constructor.
     * @param CompanyModuleUpdater $updater
     * @param ModuleService $service
     */
    public function __construct(CompanyModuleUpdater $updater, ModuleService $service)
    {
        $this->updater = $updater;
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
            $this->service->activateModules($this->updater, new CompanyModuleHistory());
        } catch (\Exception $exception) {
            \Log::error([get_class($exception), $exception->getMessage()]);
        }
    }
}
