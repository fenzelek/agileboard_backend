<?php

namespace App\Modules\TimeTracker\Jobs;

use App\Modules\TimeTracker\Services\FrameTools\WaitingFrameService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WaitingFrameActivator implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected WaitingFrameService $service;
    protected Dispatcher $event_dispatcher;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(WaitingFrameService $service, Dispatcher $event_dispatcher)
    {
        $this->service = $service;
        $this->event_dispatcher = $event_dispatcher;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->service->serveUnconvertedFrames();
        } catch (\Exception $exception) {
            \Log::error([get_class($exception), $exception->getMessage()]);
        }
    }
}
