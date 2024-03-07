<?php

namespace Tests\Unit\App\Modules\Integration\Services\ActivityTools\ActivitySplitter;

use App\Models\Db\Company;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use function factory;

trait ActivitySplitterTrait
{
    public function provideActivityTimestamps()
    {
        return [
            [
                'from' => '2022-01-01 10:00:00',
                'to' => '2022-01-01 10:00:00',
                'expected_frames_count' => 1,
            ],
            [
                'from' => '2022-01-01 10:50:00',
                'to' => '2022-01-01 11:10:00',
                'expected_frames_count' => 2,
            ],
            [
                'from' => '2022-01-01 21:15:00',
                'to' => '2022-01-02 01:15:00',
                'expected_frames_count' => 5,
            ],
        ];
    }

    private function createActivity(Project $project, Ticket $ticket, User $user, array $attributes): Activity
    {
        /**
         * @var Activity $activity
         */
        $activity = factory(Activity::class)->make($attributes);
        $activity->user()->associate($user);
        $activity->project()->associate($project);
        $activity->ticket()->associate($ticket);
        $activity->save();

        return $activity->fresh();
    }

    private function createTicket(): Ticket
    {
        return factory(Ticket::class)->create();
    }

    private function createProject(Company $company): Project
    {
        $project = factory(Project::class)->make();
        $project->company()->associate($company);
        $project->save();

        return $project;
    }

    private function createCompany(): Company
    {
        return factory(Company::class)->create();
    }
}
