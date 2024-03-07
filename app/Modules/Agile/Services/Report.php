<?php

namespace App\Modules\Agile\Services;

use App\Models\Db\History;
use App\Models\Db\Project;
use App\Models\Db\Status;
use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Project\Services\Projects as ServiceProjects;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Report
{
    /**
     * @var ServiceProjects
     */
    private $service_projects;

    private $project_ids = [];

    public function __construct(ServiceProjects $service_projects)
    {
        $this->service_projects = $service_projects;
    }

    /**
     * @param Carbon $date_from
     * @param Carbon $date_to
     * @param User $user
     * @param int|null $project_id
     *
     * @return Builder
     */
    public function getDaily(Carbon $date_from, Carbon $date_to, User $user, ?int $project_id): Builder
    {
        if ($project_id) {
            $project_ids = [$project_id];
            $user->setSelectedCompany(Project::query()->find($project_id)->company_id);
        } else {
            $project_ids = $this->getProjectIds($user);
        }

        return History::query()
            ->with('user', 'field', 'ticket')
            ->whereHas('ticket', function ($q) use ($project_ids) {
                $q->whereIn('project_id', $project_ids);
            })
            ->where('created_at', '>=', $date_from->startOfDay()->toDateTimeString())
            ->where('created_at', '<=', $date_to->endOfDay()->toDateTimeString())
            ->orderBy('created_at', 'desc');
    }

    /**
     * @param User $user
     * @return Collection
     */
    public function getProjectStatuses(User $user): Collection
    {
        return Status::query()
            ->whereIn('project_id', $this->getProjectIds($user))
            ->get()
            ->keyBy('id')
            ->groupBy('project_id', true);
    }

    /**
     * @return void
     */
    public function cleanUp(): void
    {
        $this->project_ids = [];
    }

    /**
     * @param User $user
     * @return array
     */
    protected function getProjectIds(User $user): array
    {
        if (count($this->project_ids) > 0) {
            return $this->project_ids;
        }

        $company_ids = $this->getCompanyIds($user);

        foreach ($company_ids as $company_id) {
            $user->setSelectedCompany($company_id);
            $projects = $this
                ->filterProjects($user)
                ->get()
                ->pluck('id');
            $this->project_ids = array_merge($projects->toArray(), $this->project_ids);
        }

        return $this->project_ids;
    }

    /**
     * @param User $user
     * @return array
     */
    protected function getCompanyIds(User $user): array
    {
        return $user->companies()
            ->where('user_company.status', UserCompanyStatus::APPROVED)
            ->get()
            ->pluck('id')
            ->all();
    }

    /**
     * @param User $user
     * @return mixed
     */
    protected function filterProjects(User $user)
    {
        $projects = Project::inCompany($user)->orderBy('id');

        return $projects->whereHas('users', function ($query) use ($user) {
            $query->where('project_user.user_id', $user->id);
        })->whereNull('closed_at');
    }
}
