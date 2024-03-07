<?php

namespace App\Modules\SaleInvoice\Services\Clipboard;

use App\Modules\Company\Services\ClipBoardService;
use Illuminate\Support\Collection;

class InvoicesPackage
{
    /**
     * @var Compressor
     */
    private $compressor;
    /**
     * @var ClipBoardService
     */
    private $clipboard_service;

    private $invoices;

    public function __construct(Compressor $compressor, ClipBoardService $clipboard_service)
    {
        $this->compressor = $compressor;
        $this->clipboard_service = $clipboard_service;
    }

    public function build(Collection $invoices)
    {
        if ($invoices->count() === 0) {
            return;
        }
        $company = $invoices->first()->company;
        $file_manager = new FileManager($company);
        $this->invoices = $invoices->map(function ($invoice) use ($file_manager) {
            try {
                $pdf = (new Printer($invoice))->render();
                $file_name = 'faktura-' . str_slug($invoice->number) . '.pdf';
                $file_manager->save($pdf, $file_name);
            } catch (\Exception $exception) {
                \Log::error([get_class($exception), $exception->getMessage()]);

                return;
            }

            return $file_name;
        })->reject(function ($invoice) {
            return empty($invoice);
        });

        try {
            $zip_file = $this->compressor->zip($file_manager, $this->invoices->all());
            $this->clipboard_service->store($company, $zip_file);
        } catch (\Exception $exception) {
            \Log::error([get_class($exception), $exception->getMessage()]);
        }
        $this->invoices->each(function ($invoice) use ($file_manager) {
            $file_manager->delete($invoice);
        });
    }
}
