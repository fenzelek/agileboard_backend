<?php

namespace App\Modules\Company\Services;

use App\Models\Db\Clipboard;
use App\Models\Db\Company;
use App\Modules\Company\Exceptions\NoFileInClipboard;
use App\Modules\SaleInvoice\Services\Clipboard\FileManager;
use Carbon\Carbon;

class ClipBoardService
{
    /**
     * @var Clipboard
     */
    private $clipboard;
    /**
     * @var FileManager
     */
    private $file_manager;

    /**
     * Company constructor.
     * @param Clipboard $clipboard
     */
    public function __construct(Clipboard $clipboard)
    {
        $this->clipboard = $clipboard;
    }

    public function store(Company $company, $file_name)
    {
        $this->file_manager = new FileManager($company);
        if (! $this->file_manager->isExists($file_name)) {
            throw new NoFileInClipboard();
        }
        $company->clipboard()->create(compact('file_name'));
    }

    public function delete(Clipboard $clipboard)
    {
        $this->file_manager = new FileManager($clipboard->company);

        $this->file_manager->delete($clipboard->file_name);

        $clipboard->delete();
    }

    public function cleanupClipboard()
    {
        $cleanup_clipboard_period = config('app_settings.cleanup_clipboard');
        $cleanup_time = Carbon::now()->subDay($cleanup_clipboard_period);
        $this->clipboard
            ->whereDate('created_at', '<', $cleanup_time)
            ->get()->each(function ($clipboard) {
                $this->delete($clipboard);
            });
    }
}
