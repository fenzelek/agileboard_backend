<?php

namespace App\Modules\Gantt\Services;

use App\Models\Db\Project;
use App\Models\Db\Sprint;
use Carbon\Carbon;
use App\Modules\Gantt\Services\Interfaces\Workload;

class SprintWorkload implements Workload
{
    public $rate = 100;
    public $start_at;
    public $end_at;
    public $project;
    public $sprint;

    /**
     * @param Carbon $minimum_date
     */
    public function setStartDate(Carbon $minimum_date)
    {
        $this->start_at = $this->start_at < $minimum_date
            ? $minimum_date
            : $this->start_at;
    }

    /**
     * @param Carbon $start_date
     * @param Carbon $end_date
     */
    public function setEndDate(Carbon $start_date, Carbon $end_date)
    {
        $this->end_at = $this->end_at > $end_date
            ? $end_date
            : $this->end_at;
    }

    /**
     * @param int $rate
     * @return int
     */
    public function setRate(int $rate): int
    {
        $this->rate = $rate;
    }

    /**
     * @param Carbon $from_date
     * @param Carbon $to_date
     * @return bool
     */
    public function withinPeriod(Carbon $from_date, Carbon $to_date): bool
    {
        if (! $from_date || ! $to_date) {
            return false;
        }

        if ($this->start_at->greaterThanOrEqualTo($from_date) && $this->start_at->lessThanOrEqualTo($to_date)) {
            return true;
        }

        if ($this->end_at->greaterThanOrEqualTo($from_date) && $this->end_at->lessThanOrEqualTo($to_date)) {
            return true;
        }

        return false;
    }

    /**
     * @param Carbon $date
     */
    public function setWorkloadStart(Carbon $date)
    {
        $this->start_at = $date;
    }

    /**
     * @param Carbon $date
     */
    public function setWorkloadEnd(Carbon $date)
    {
        $this->end_at = $date;
    }

    /**
     * @param Project $project
     */
    public function setProject(Project $project)
    {
        $this->project = $project->only('id', 'name', 'color');
    }

    /**
     * @param Sprint $sprint
     */
    public function setSprint(Sprint $sprint)
    {
        $this->sprint = $sprint->only('id', 'name');
    }

    /**
     * @return Carbon
     */
    public function getWorkloadStart(): Carbon
    {
        return $this->start_at;
    }
}
