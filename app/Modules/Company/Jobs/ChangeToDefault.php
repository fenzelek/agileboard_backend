<?php

namespace App\Modules\Company\Jobs;

use App\Models\Db\CompanyModule;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Company\Services\Payments\ModuleService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ChangeToDefault implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $updater;
    private $service;

    /**
     * ChangeToDefault constructor.
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
            $this->service->changeToDefault(new CompanyModule(), $this->updater);
        } catch (\Exception $exception) {
            \Log::error([get_class($exception), $exception->getMessage()]);
        }
    }
}
