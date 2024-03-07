<?php

namespace Tests\Unit\App\Modules\Integration\Services\ActivityReport;

use App\Models\Db\Company;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Models\Db\UserCompany;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Integration\Models\ManualTicketActivityDto;
use Carbon\Carbon;

trait ActivityReportTrait
{
    protected function prepareUserProvideAvailabilitiesData(): array
    {
        $day = Carbon::parse('2022-12-10');

        $user = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject($company->id);
        $ticket_tracked_seconds = 3600;
        $this->addUserToCompany($user, $company);

        $ticket = $this->createTicket($project->id);
        $this->createTicketActivity($user, $ticket, true, $day->addHours(4), $ticket_tracked_seconds);

        $start_dates = [(clone $day)->addHours(2), (clone $day)->addHours(5)];
        $end_dates = [(clone $day)->addHours(4), (clone $day)->addHours(8)];
        $available = true;

        $this->createUserAvailability($user, $company, $day, $available, $start_dates[0], $end_dates[0]);
        $this->createUserAvailability($user, $company, $day, $available, $start_dates[1], $end_dates[1]);

        $availability_seconds = $end_dates[0]->diffInSeconds($start_dates[0]) + $end_dates[1]->diffInSeconds($start_dates[1]);

        return [
            'day' => $day,
            'company_id' => $company->id,
            'is_available' => true,
            'availability_seconds' => $availability_seconds,
            'work_progress' => $ticket_tracked_seconds/$availability_seconds,
        ];
    }

    protected function prepareUserHasActivitiesFromOtherDayData(): array
    {
        $given_day = Carbon::parse('2022-12-10');
        $other_day = Carbon::parse('2022-12-11');

        $user = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject($company->id);
        $this->addUserToCompany($user, $company);
        $ticket = $this->createTicket($project->id);

        $this->createTicketActivity($user, $ticket, true, $given_day->addHours(4), 20);
        $this->createTicketActivity($user, $ticket, false, $given_day->addHours(2), 40);
        $this->createTicketActivity($user, $ticket, true, $other_day->addHours(5), 30);

        return [
            'day' => $given_day,
            'company_id' => $company->id,
            'expected_manual_activities' => 20,
            'expected_tracking_activities' => 40,
            'expected_manual_activity_tickets' => [
                [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'name' => $ticket->name,
                    'manual_activity' => 20,
                ],
            ],
        ];
    }

    protected function prepareUserHasTrackingActivitiesInOtherCompanyData(): array
    {
        $utc_started_at = Carbon::parse('2023-12-10 12:00');

        $user = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject($company->id);

        $this->prepareActivitiesFromOtherCompany($user, $utc_started_at);

        $this->addUserToCompany($user, $company);
        $tickets = [$this->createTicket($project->id), $this->createTicket($project->id)];

        $manual_activity = true;
        $this->createTicketActivity($user, $tickets[0], $manual_activity, $utc_started_at, 10);
        $this->createTicketActivity($user, $tickets[1], $manual_activity, $utc_started_at, 20);

        $this->createTicketActivity($user, $tickets[0], ! $manual_activity, $utc_started_at, 30);
        $this->createTicketActivity($user, $tickets[0], ! $manual_activity, $utc_started_at, 40);

        return [
            'day' => Carbon::parse($utc_started_at->toDateString()),
            'company_id' => $company->id,
            'expected_manual_activities' => 10+20,
            'expected_tracking_activities' => 30+40,
            'expected_manual_activity_tickets' => [
                [
                    'id' => $tickets[0]->id,
                    'title' => $tickets[0]->title,
                    'name' => $tickets[0]->name,
                    'manual_activity' => 10,
                ],
                [
                    'id' => $tickets[1]->id,
                    'title' => $tickets[1]->title,
                    'name' => $tickets[1]->name,
                    'manual_activity' => 20,
                ],
            ],
        ];
    }

    protected function prepareOtherUserHasActivitiesData(): array
    {
        $utc_started_at = Carbon::parse('2023-12-10 12:00');

        $user = $this->createNewUser();
        $other_user = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject($company->id);
        $ticket = $this->createTicket($project->id);

        $this->addUserToCompany($user, $company);
        $this->addUserToCompany($other_user, $company);

        $manual_activity = true;
        $this->createTicketActivity($user, $ticket, $manual_activity, $utc_started_at, 100);
        $this->createTicketActivity($user, $ticket, ! $manual_activity, $utc_started_at, 20);
        $this->createTicketActivity($other_user, $ticket, ! $manual_activity, $utc_started_at, 30);

        return [
            'user_id' => $user->id,
            'day' => Carbon::parse($utc_started_at->toDateString()),
            'company_id' => $company->id,
            'expected_manual_activities' => 100,
            'expected_tracking_activities' => 20,
            'expected_manual_activity_tickets' => [
                [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'name' => $ticket->name,
                    'manual_activity' => 100,
                ],
            ],
        ];
    }

    protected function assertManualTicketActivityCorrect(array $expected, ManualTicketActivityDto $dto)
    {
        $this->assertSame($dto->getTicketId(), $expected['id']);
        $this->assertSame($dto->getTicketTitle(), $expected['title']);
        $this->assertSame($dto->getTicketName(), $expected['name']);
        $this->assertSame($dto->getManualActivity(), $expected['manual_activity']);
    }

    protected function prepareActivitiesFromOtherCompany(User $user, Carbon $utc_started_at)
    {
        $company = $this->createCompany();
        $project = $this->createProject($company->id);
        $this->addUserToCompany($user, $company);

        $ticket = $this->createTicket($project->id);
        $manual_activity = true;
        $this->createTicketActivity($user, $ticket, $manual_activity, $utc_started_at, 10);
        $this->createTicketActivity($user, $ticket, ! $manual_activity, $utc_started_at, 10);
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

    protected function createUserAvailability(
        User $user,
        Company $company,
        Carbon $day,
        bool $available,
        ?Carbon $start_date=null,
        ?Carbon $end_date=null
    ): UserAvailability {
        return factory(UserAvailability::class)->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'day' => $day->toDateString(),
            'time_start' => $start_date ? $start_date->toTimeString() : null,
            'time_stop' => $end_date ? $end_date->toTimeString() : null,
            'available' => $available,
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
