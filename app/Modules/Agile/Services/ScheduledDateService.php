<?php

namespace App\Modules\Agile\Services;

use App\Models\Db\Status;
use App\Models\Db\Ticket;
use App\Modules\Agile\Events\ExpiredScheduledDateEvent;
use App\Modules\Agile\Events\TodayScheduledDateEvent;
use Carbon\Carbon;

class ScheduledDateService
{
    /**
     * Check expired date end.
     */
    public function checkExpired()
    {
        $day = Carbon::now()->subDay()->startOfDay();
        $this->getData($day, function ($project, $ticket) {
            event(new ExpiredScheduledDateEvent($project, $ticket));
        });
    }

    /**
     * Check today is date end.
     */
    public function checkOnDate()
    {
        $day = Carbon::now()->startOfDay();
        $this->getData($day, function ($project, $ticket) {
            event(new TodayScheduledDateEvent($project, $ticket));
        });
    }

    /**
     * Get data for events.
     *
     * @param $day_start
     * @param $callback
     */
    private function getData($day_start, $callback)
    {
        $day_stop = (clone $day_start)->endOfDay();
        $tickets = Ticket::where('scheduled_time_end', '>=', $day_start)
            ->where('scheduled_time_end', '<=', $day_stop)
            ->whereNotNull('assigned_id')
            ->orderBy('project_id')
            ->get();

        //for cache
        $current_project = 0;
        $last_status = null;

        foreach ($tickets as $ticket) {
            //set cache
            if ($ticket->project_id != $current_project) {
                $current_project = $ticket->project_id;
                $last_status = Status::lastStatus($current_project);
            }

            if ($last_status->id != $ticket->status_id) {
                $callback($ticket->project, $ticket);
            }
        }
    }
}
