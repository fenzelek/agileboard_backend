<?php

namespace App\Modules\SaleInvoice\Jobs;

use App\Modules\SaleInvoice\Services\Clipboard\InvoicesPackage;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateInvoicesPackage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * @var Collection
     */
    public $invoices;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $invoices)
    {
        $this->invoices = $invoices;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $invoice_package = app()->make(InvoicesPackage::class);
            $invoice_package->build($this->invoices);
        } catch (\Exception $exception) {
            \Log::error([get_class($exception), $exception->getMessage()]);
        }
    }
}
