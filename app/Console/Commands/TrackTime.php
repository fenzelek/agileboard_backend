<?php

namespace App\Console\Commands;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Modules\Integration\Services\TimeTracking\TrackTime as TrackTimeService;
use Exception;
use Illuminate\Console\Command;
use Log;

class TrackTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:track-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Track time for companies with active Time tracking integration';

    /**
     * @var TrackTimeService
     */
    protected $time_tracking_service;

    /**
     * Create a new command instance.
     *
     * @param TrackTimeService $time_tracking_service
     */
    public function __construct(TrackTimeService $time_tracking_service)
    {
        parent::__construct();
        $this->time_tracking_service = $time_tracking_service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Integration::active()->ofType(IntegrationProvider::TYPE_TIME_TRACKING)->with('provider')
            ->get()->each(function ($integration) {
                try {
                    $this->info("Integration #{$integration->id} was started");
                    $this->time_tracking_service->fetch($integration);
                    $this->info("Integration #{$integration->id} was completed");
                } catch (Exception $e) {
                    // we only log error, other integration might work without any problem so we
                    // don't want to make any other integrations stopped
                    Log::error($e);
                    $this->error($e);
                }
            });
    }

    public function info($string, $verbosity = null)
    {
        parent::info($string, $verbosity);
        \Illuminate\Support\Facades\Log::channel('time-tracking')->info($string);
    }
}
