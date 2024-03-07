<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Models\Db\Story;
use App\Models\Db\User;
use App\Models\Db\UserCompany;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use Carbon\Carbon;

trait TestTrait
{
    /** @var Carbon */
    protected $now;
    /** @var Company */
    protected $company;
    /** @var Project */
    protected $project;
    /** @var Sprint */
    protected $sprint;
    /** @var Status */
    protected $status;
    /** @var Story */
    protected $story;

    /**
     * @param string $role
     */
    protected function initEnv($role = RoleType::ADMIN)
    {
        $this->now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($this->now);
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = $this->createCompanyWithRole($role);
        $this->createUserCompany($role);
        $this->project = $this->createProject('PROJ');
        $this->setProjectRole($this->project, $role);
        $this->sprint = $this->createSprint('test', 1, Sprint::INACTIVE);
        $this->story = factory(Story::class)->create(['project_id' => $this->project->id]);

        $this->status = $this->createStatus(1);
        $this->project->update(['status_for_calendar_id' => $this->status->id]);
    }

    /**
     * @param int $priority
     * @param int|null $project_id
     *
     * @return Status
     */
    protected function createStatus(int $priority, int $project_id = null): Status
    {
        $project_id = $project_id ?: $this->project->id;

        return factory(Status::class)->create([
            'project_id' => $project_id,
            'priority' => $priority,
        ]);
    }

    /**
     * @param $role
     *
     * @return User
     */
    protected function createUserCompany($role): User
    {
        $other_user_in_company = factory(User::class)->create();
        UserCompany::create([
            'user_id' => $other_user_in_company->id,
            'company_id' => $this->company->id,
            'role_id' => Role::findByName($role)->id,
            'status' => UserCompanyStatus::DELETED,
        ]);

        return $other_user_in_company;
    }

    /**
     * @param string $short_name
     *
     * @return Project
     */
    protected function createProject(string $short_name): Project
    {
        return factory(Project::class)
            ->create([
                'company_id' => $this->company->id,
                'short_name' => $short_name,
                'created_tickets' => 1,
                'ticket_scheduled_dates_with_time' => true,
            ]);
    }

    /**
     * @param string $name
     *
     * @param int $priority
     * @param string $status
     *
     * @return Sprint
     */
    protected function createSprint(string $name, int $priority, string $status): Sprint
    {
        return factory(Sprint::class)->create([
            'project_id' => $this->project->id,
            'name' => $name,
            'priority' => $priority,
            'status' => $status,
        ]);
    }
}
