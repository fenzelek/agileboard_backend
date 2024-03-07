<?php

namespace App\Modules\Gantt\Services;

use Carbon\Carbon;
use App\Models\Db\Project;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use App\Models\CustomCollections\UsersCollection;
use App\Modules\Gantt\Services\Interfaces\WorkloadCalc;

class WorkloadService
{
    private $workload_user;
    private $workload_calc;

    /**
     * WorkloadService constructor.
     *
     * @param WorkloadUser $workload_user
     * @param Container $app
     */
    public function __construct(WorkloadUser $workload_user, Container $app)
    {
        $this->workload_user = $workload_user;
        $this->workload_calc = $this->getWorkloadCalc($app);
    }

    /**
     * @param Carbon $chart_start_date
     * @param Carbon $chart_end_date
     * @param int $company_id
     * @return array
     */
    public function prepare(Carbon $chart_start_date, Carbon $chart_end_date, int $company_id): array
    {
        $workload_users = $this->workload_user->find($company_id);

        $workloads = $this->prepareUsersWorkload($workload_users, $chart_start_date, $chart_end_date);

        return $workloads;
    }

    /**
     * @param UsersCollection $workload_users
     * @param Carbon $chart_start_date
     * @param Carbon $chart_end_date
     * @return array
     */
    private function prepareUsersWorkload(UsersCollection $workload_users, Carbon $chart_start_date, Carbon $chart_end_date): array
    {
        $user_ids = $workload_users->pluck('id')->all();

        $projects = Project::hasUsers($user_ids)->with('sprints')->get();
        $collection = $this->workload_calc->calc($workload_users, $projects, $chart_start_date, $chart_end_date);

        return $this->transform($collection);
    }

    /**
     * @param Collection $collection
     * @return array
     */
    private function transform(Collection $collection): array
    {
        $data = [];
        foreach ($collection as $item) {
            $item = $this->changeDateFormat($item);

            $data[] = [
                'user' => $item->only('avatar', 'email', 'first_name', 'id', 'last_name'),
                'workloads' => $item->workloads ?: [],
            ];
        }

        return $data;
    }

    /**
     * Change date format from Carbon to DateTimeString.
     *
     * @param $item
     * @return mixed
     */
    private function changeDateFormat($item)
    {
        if (! $item->workloads) {
            return $item;
        }

        foreach ($item->workloads as $key => $workload) {
            $item->workloads[$key]->start_at = $this->toString($item->workloads[$key]->start_at);
            $item->workloads[$key]->end_at = $this->toString($item->workloads[$key]->end_at);
        }

        return $item;
    }

    /**
     * Change Carbon to string if isn't string yet.
     *
     * @param $date
     * @return mixed
     */
    private function toString($date)
    {
        if (is_string($date)) {
            return $date;
        }

        return $date->toDateTimeString();
    }

    /**
     * @param Container $app
     * @return WorkloadCalc
     */
    private function getWorkloadCalc(Container $app): WorkloadCalc
    {
        return $app->make(SprintWorkloadCalc::class, [
            'sprint_hours_calc' => $app->make(SprintHoursCalc::class),
            'sprint_period_calc' => $app->make(SprintPeriodCalc::class),
        ]);
    }
}
