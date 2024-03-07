<?php

namespace App\Console\Commands;

use App\Models\Db\File;
use App\Modules\Project\Services\Storage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteTempFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:delete-temp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete temporary files after one hours after creation,';

    private $storage;

    /**
     * Create a new command instance.
     *
     * @param Storage $storage
     */
    public function __construct(Storage $storage)
    {
        parent::__construct();

        $this->storage = $storage;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $files = File::where('temp', true)->where('created_at', '<', Carbon::now()->subHour())->get();

        foreach ($files as $file) {
            DB::transaction(function () use ($file) {
                $file->delete();
                $file->roles()->detach();
                $file->users()->detach();

                $this->storage->remove($file->project->company_id, $file->project->id, $file->storage_name);
            });
        }
    }
}
