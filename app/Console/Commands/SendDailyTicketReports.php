<?php

namespace App\Console\Commands;

use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Agile\Services\Report;
use App\Notifications\DailyTicketReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyTicketReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily-ticket-report:send {--company=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily ticket reports by emails';

    /**
     * @var Report
     */
    private $report_service;

    /**
     * Create a new job instance.
     *
     * @param Report $report_service
     */
    public function __construct(Report $report_service)
    {
        parent::__construct();
        $this->report_service = $report_service;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // TODO: use when settings will be ready
//        $users = User::query()->where('daily_ticket_report', true)->get();

        $users_query = User::query();
        Log::info('sending daily report');

        if ($this->option('company')) {
            $users_query->whereHas('companies', function ($q) {
                $q->where('name', $this->option('company'))
                    ->where('user_company.status', UserCompanyStatus::APPROVED);
            });
        } else {
            $users_query->whereHas('companies', function ($q) {
                $q->where('user_company.status', UserCompanyStatus::APPROVED);
            });
        }

        $users = $users_query->get();

        $date_from = now()->subDay()->startOfDay();
        $date_to = now()->subDay()->endOfDay();

        foreach ($users as $user) {
            $report_query = $this->report_service->getDaily($date_from, $date_to, $user, null);
            $report_data = $report_query->get();

            if (count($report_data) === 0) {
                $this->report_service->cleanUp();
                continue;
            }

            $report_data = $report_data->groupBy('ticket.project_id')->toArray();
            $report_data = $this->cutRedundantPriorityChanges($report_data);

            $project_statuses = $this->report_service->getProjectStatuses($user);
            $this->report_service->cleanUp();

            $user->notify(new DailyTicketReport($report_data, $project_statuses->toArray(), $date_from));
        }
    }

    /**
     * @param array $report_data
     * @return array
     */
    private function cutRedundantPriorityChanges(array $report_data): array
    {
        foreach ($report_data as $k => $row) {
            $last_found_ticket = null;
            $last_found_key = null;
            foreach ($row as $kk => $change) {
                if ($change['field']['object_type'] == 'ticket' && $change['field']['field_name'] == 'status_id') {
                    $last_found_key = $kk;
                    $last_found_ticket = $change['ticket']['id'];
                    continue;
                }

                if ($change['field']['object_type'] == 'ticket'
                    && $change['field']['field_name'] == 'priority'
                    && $kk === $last_found_key + 1
                    && $change['ticket']['id'] === $last_found_ticket) {
                    unset($report_data[$k][$kk]);
                    $last_found_ticket = null;
                    $last_found_key = null;
                }
            }
        }

        return $report_data;
    }
}
