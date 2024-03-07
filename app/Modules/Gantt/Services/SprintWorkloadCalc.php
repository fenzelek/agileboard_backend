<?php

namespace App\Modules\Gantt\Services;

use Carbon\Carbon;
use App\Models\Db\Sprint;
use App\Models\Db\Project;
use Illuminate\Container\Container;
use App\Models\CustomCollections\UsersCollection;
use App\Modules\Gantt\Services\Interfaces\Workload;
use App\Models\CustomCollections\ProjectsCollection;
use App\Modules\Gantt\Services\Interfaces\HoursCalc;
use App\Modules\Gantt\Services\Interfaces\PeriodCalc;
use App\Modules\Gantt\Services\Interfaces\WorkloadCalc;

class SprintWorkloadCalc implements WorkloadCalc
{
    private $sprint_hours_calc;
    private $sprint_period_calc;
    private $app;

    /**
     * SprintWorkloadCalc constructor.
     * @param HoursCalc $sprint_hours_calc
     * @param PeriodCalc $sprint_period_calc
     * @param Container $app
     */
    public function __construct(HoursCalc $sprint_hours_calc, PeriodCalc $sprint_period_calc, Container $app)
    {
        $this->sprint_hours_calc = $sprint_hours_calc;
        $this->sprint_period_calc = $sprint_period_calc;
        $this->app = $app;
    }

    /**
     * @param UsersCollection $workload_users
     * @param ProjectsCollection $projects
     * @param Carbon $chart_start_date
     * @param Carbon $chart_end_date
     * @return UsersCollection
     */
    public function calc(UsersCollection $workload_users, ProjectsCollection $projects, Carbon $chart_start_date, Carbon $chart_end_date): UsersCollection
    {
        foreach ($projects as $project) {
            $project_related_users = $workload_users->getProjectRelated($project->id);
            $this->forProject($project, $project_related_users, $chart_start_date, $chart_end_date);
        }

        return $workload_users;
    }

    /**
     * @param Project $project
     * @param UsersCollection $workload_users
     * @param Carbon $chart_start_date
     * @param Carbon $chart_end_date
     */
    private function forProject(Project $project, UsersCollection $workload_users, Carbon $chart_start_date, Carbon $chart_end_date)
    {
        foreach ($project->sprints as $sprint) {
            $this->forSprint($project, $sprint, $workload_users, $chart_start_date, $chart_end_date);
        }
    }

    /**
     * @return Workload
     */
    private function createWorkload(): Workload
    {
        return $this->app->make(SprintWorkload::class);
    }

    /**
     * @param Project $project
     * @param Sprint $sprint
     * @param UsersCollection $workload_users
     * @param Carbon $chart_start_date
     * @param Carbon $chart_end_date
     */
    private function forSprint(Project $project, Sprint $sprint, UsersCollection $workload_users, Carbon $chart_start_date, Carbon $chart_end_date)
    {
        $workload_sprint = $this->createWorkload();

        if (! $this->hasStartDate($sprint)) {
            return;
        }

        if (! $this->canSetEnd($sprint)) {
            return;
        }

        $this->setWorkloadStart($workload_sprint, $sprint);
        $this->setWorkloadEnd($workload_sprint, $sprint);

        if (! $workload_sprint->withinPeriod($chart_start_date, $chart_end_date)) {
            return;
        }

        $workload_sprint->setProject($project);
        $workload_sprint->setSprint($sprint);

        $this->cutToPeriod($workload_sprint, $chart_start_date, $chart_end_date);
        $this->assignUsers($workload_users, $workload_sprint);
    }

    /**
     * @param Sprint $sprint
     * @return bool
     */
    private function hasStartDate(Sprint $sprint)
    {
        if ($sprint->activated_at || $sprint->planned_activation) {
            return true;
        }

        return false;
    }

    /**
     * @param Sprint $sprint
     * @return bool
     */
    private function canSetEnd(Sprint $sprint)
    {
        if ($sprint->planned_closing) {
            return true;
        }

        if ($this->sprint_hours_calc->calc($sprint) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param Workload $workload_sprint
     * @param $sprint
     */
    private function setWorkloadStart(Workload $workload_sprint, $sprint)
    {
        $workload_sprint->setWorkloadStart($sprint->activated_at ?: $sprint->planned_activation);
    }

    /**
     * @param Workload $workload_sprint
     * @param $sprint
     * @return mixed
     */
    private function setWorkloadEnd(Workload $workload_sprint, $sprint)
    {
        if ($sprint->planned_closing) {
            return $workload_sprint->setWorkloadEnd($sprint->planned_closing);
        }

        $hours_summary = $this->sprint_hours_calc->calc($sprint);
        $days_count = $this->sprint_period_calc->calcDays($hours_summary);
        $date = $this->sprint_period_calc->calcEndDate($workload_sprint->getWorkloadStart(), $days_count);
        $workload_sprint->setWorkloadEnd($date);
    }

    /**
     * @param Workload $workload_sprint
     * @param Carbon $chart_start_date
     * @param Carbon $chart_end_date
     */
    private function cutToPeriod(Workload $workload_sprint, Carbon $chart_start_date, Carbon $chart_end_date)
    {
        $workload_sprint->setStartDate($chart_start_date);
        $workload_sprint->setEndDate($chart_start_date, $chart_end_date);
    }

    /**
     * @param UsersCollection $workload_users
     * @param Workload $workload_sprint
     * @return UsersCollection
     */
    private function assignUsers(UsersCollection $workload_users, Workload $workload_sprint): UsersCollection
    {
        $workload_users->map(function ($workload_user) use ($workload_sprint) {
            return $workload_user->workloads[] = $workload_sprint;
        });

        return $workload_users;
    }
}
