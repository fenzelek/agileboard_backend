<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Integration\Services\TimeTrackerActivities;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Modules\Integration\Models\TimeTrackingActivity;
use Illuminate\Support\Collection;

trait TimeTrackerActivitiesTrait
{
    protected function createActivityForExport(
        bool $activity_has_user,
        ?string $user_first_name,
        ?string $user_last_name,
        ?string $utc_started_at,
        ?string $utc_finished_at,
        int $tracked_seconds,
        int $activity_seconds,
        string $project_name,
        string $sprint_name,
        ?string $ticket_title
    ) {
        $user = $activity_has_user ? factory(User::class)->create([
            'first_name' => $user_first_name,
            'last_name' => $user_last_name,
        ]) : null;

        $project = factory(Project::class)->create([
            'name' => $project_name,
        ]);
        $sprint = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'name' => $sprint_name,
        ]);
        $ticket = factory(Ticket::class)->create([
            'title' => $ticket_title,
            'sprint_id' => $sprint->id,
        ]);

        return factory(Activity::class)->create([
            'user_id' => $user ? $user->id : null,
            'project_id' => $project->id,
            'ticket_id' => $ticket->id,
            'utc_started_at' => $utc_started_at,
            'utc_finished_at' => $utc_finished_at,
            'tracked' => $tracked_seconds,
            'activity' => $activity_seconds,
        ]);
    }

    public function activityDataProvider(): array
    {
        return [
            [
                'activity_has_user' => false,
                'user_first_name' => '',
                'user_last_name' => '',
                'utc_started_at' => null,
                'utc_finished_at' => null,
                'tracked_seconds' => 60,
                'activity_seconds' => 12,
                'project_name' => 'Project',
                'sprint_name' => 'Sprint',
                'ticket_title' => null,
            ],
            [
                'activity_has_user' => true,
                'user_first_name' => 'Paweł',
                'user_last_name' => 'Kowalski',
                'utc_started_at' => '2023-01-01 12:00:00',
                'utc_finished_at' => '2023-01-05 12:00:00',
                'tracked_seconds' => 60,
                'activity_seconds' => 12,
                'project_name' => 'Project',
                'sprint_name' => 'Sprint',
                'ticket_title' => null,
            ],
        ];
    }

    protected function createTicketActivityForSummaryExport(
        bool $activity_has_user,
        string $user_first_name,
        string $user_last_name,
        bool $has_ticket,
        ?string $ticket_title,
        ?string $ticket_description,
        int $estimate_time,
        array $tracked_times,
        ?string $sprint_name,
        ?string $project_name
    ): ?Ticket {
        $user = $activity_has_user ? factory(User::class)->create([
            'first_name' => $user_first_name,
            'last_name' => $user_last_name,
        ]) : null;

        $project = factory(Project::class)->create([
            'name' => $project_name??'',
        ]);

        $sprint = factory(Sprint::class)->create([
            'project_id' => $project,
            'name' => $sprint_name??'',
        ]);
        $ticket = $has_ticket ? factory(Ticket::class)->create([
            'project_id' => $project ? $project->id : null,
            'sprint_id' => $sprint ? $sprint->id : null,
            'title' => $ticket_title,
            'description' => $ticket_description,
            'estimate_time' => $estimate_time,
        ]) : null;

        foreach ($tracked_times as $tracked_time) {
            factory(Activity::class)->create([
                'user_id' => $user ? $user->id : null,
                'project_id' => $project->id,
                'ticket_id' => $ticket ? $ticket->id : null,
                'tracked' => $tracked_time,
            ]);
        }

        return $ticket;
    }

    public function activityForSummaryDataProvider(): array
    {
        return [
            [
                'activity_has_user' => true,
                'user_first_name' => 'Paweł',
                'user_last_name' => 'Polak',
                'has_ticket' => true,
                'ticket_title' => 'AB-1250',
                'ticket_description' => 'Desc',
                'estimate_time' => 12000,
                'tracked_times' => [50,40,10,60],
                'sprint_name' => 'Sprint',
                'project_name' => 'Project',
            ],
            [
                'activity_has_user' => false,
                'user_first_name' => '',
                'user_last_name' => '',
                'has_ticket' => true,
                'ticket_title' => null,
                'ticket_description' => null,
                'estimate_time' => 0,
                'tracked_times' => [50,40],
                'sprint_name' => 'Sprint name',
                'project_name' => 'Project name',
            ],
            [
                'activity_has_user' => true,
                'user_first_name' => '',
                'user_last_name' => '',
                'has_ticket' => false,
                'ticket_title' => null,
                'ticket_description' => null,
                'estimate_time' => 0,
                'tracked_times' => [50,40],
                'sprint_name' => null,
                'project_name' => null,
            ],
        ];
    }
}
