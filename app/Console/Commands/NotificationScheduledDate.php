<?php

namespace App\Console\Commands;

use App\Modules\Agile\Services\ScheduledDateService;
use Illuminate\Console\Command;

class NotificationScheduledDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:scheduled-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notify for assign user to task where scheduled date was expired or is today.';

    private $service;

    /**
     * Create a new command instance.
     *
     * NotificationScheduledDate constructor.
     * @param ScheduledDateService $service
     */
    public function __construct(ScheduledDateService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->service->checkExpired();
        $this->service->checkOnDate();
    }
}
