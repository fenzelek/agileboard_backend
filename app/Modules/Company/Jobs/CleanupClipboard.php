<?php

namespace App\Modules\Company\Jobs;

use App\Modules\Company\Services\ClipBoardService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CleanupClipboard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var ClipBoardService
     */
    private $clipboard_service;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ClipBoardService $clipboard_service)
    {
        $this->clipboard_service = $clipboard_service;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->clipboard_service->cleanupClipboard();
        } catch (\Exception $exception) {
            \Log::error([get_class($exception), $exception->getMessage()]);
        }
    }
}
