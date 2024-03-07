<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController\ActivityReport;

use App\Models\Db\Company;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Db\UserCompany;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use Carbon\Carbon;

trait ActivityReportTrait
{
    public function allowedRoleTypeProvider(): array
    {
        return [
            [RoleType::ADMIN],
            [RoleType::OWNER],
        ];
    }

    public function notAllowedRoleTypeProvider(): array
    {
        return [
            [RoleType::DEVELOPER],
            [RoleType::CLIENT],
        ];
    }

    protected function expectedSuccessStructure(): array
    {
        return [
            'data' => [
                [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                    'is_available',
                    'availability_seconds',
                    'work_progress',
                    'manual_activity_tickets' => [
                        [
                            'id',
                            'title',
                            'name',
                            'manual_activity',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function prepareActivityForUserCompany(User $user, Company $company, string $company_role, Carbon $day): void
    {
        Ticket::unsetEventDispatcher();
        $project = $this->createProject($company->id);
        $this->addUserToCompany($user, $company, $company_role);
        $user = $this->createNewUser();
        $this->addUserToCompany($user, $company);
        $ticket = $this->createTicket($project->id);

        $this->createTicketActivity($user, $ticket, true, $day, 20);
    }

    protected function createTicketActivity(User $user, Ticket $ticket, bool $manual, Carbon $utc_started_at, int $tracked): Activity
    {
        return factory(Activity::class)->create([
            'project_id' => $ticket->project_id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'tracked' => $tracked,
            'external_activity_id' => $manual ? 'manual_activity' : 'activity_' . $ticket->id,
            'utc_started_at' => $utc_started_at,
            'utc_finished_at' => $utc_started_at->addSeconds($tracked),
        ]);
    }

    protected function createProject(int $company_id): Project
    {
        return factory(Project::class)->create(['company_id' => $company_id]);
    }

    protected function createTicket(int $project_id): Ticket
    {
        return factory(Ticket::class)->create(['project_id' => $project_id]);
    }

    protected function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    protected function addUserToCompany(User $user, Company $company, string $role=RoleType::DEVELOPER): void
    {
        UserCompany::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role_id' => Role::query()->where('name', $role)->first()->id,
            'status' => UserCompanyStatus::APPROVED,
        ]);
    }
}
