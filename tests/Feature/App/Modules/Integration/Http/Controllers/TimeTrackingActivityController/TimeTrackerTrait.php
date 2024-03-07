<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController;

use App\Models\Db\Company;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Integration\TimeTracking\Note;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\TimeTracker\Screen;
use Carbon\Carbon;

trait TimeTrackerTrait
{
    protected function createTimeTrackingActivities()
    {
        //#0
        $tracking_activities = [];
        array_push($tracking_activities, (factory(Activity::class)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'user_id' => $this->user->id,
            'project_id' => null,
            'ticket_id' => null,
            'external_activity_id' => 'ABC',
            'time_tracking_user_id' => $this->tracking_users[4]->id,
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
            'utc_started_at' => Carbon::now()->subDays(10)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->subDays(3)->toDateTimeString(),
            'comment' => 'Test abc def',
            'tracked' => 5,
            'activity' => 0, // activity_level 0%
        ])));

        //#1
        array_push($tracking_activities, (factory(Activity::class)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'user_id' => null,
            'project_id' => $this->project->id,
            'ticket_id' => null,
            'external_activity_id' => 'ABC',
            'time_tracking_user_id' => $this->tracking_users[3]->id,
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
            'utc_started_at' => Carbon::now()->subDays(9)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->subDays(4)->toDateTimeString(),
            'comment' => 'Supercomment',
            'tracked' => 600,
            'activity' => 300, // activity_level 50%
        ])));

        $note = factory(Note::class)->create(['content' => ' TEST ABC']);

        //#2
        array_push($tracking_activities, (factory(Activity::class)->create([
            'integration_id' => $this->upwork_integration->id,
            'user_id' => null,
            'project_id' => null,
            'ticket_id' => $this->ticket->id,
            'external_activity_id' => 'ABC',
            'time_tracking_user_id' => $this->tracking_users[2]->id,
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => $note->id,
            'utc_started_at' => Carbon::now()->subDays(8)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->subDays(3)->toDateTimeString(),
            'comment' => 'ABC',
            'tracked' => 500,
            'activity' => 200, // activity_level 40%
        ])));

        $note_2 = factory(Note::class)->create(['content' => ' TEST WWW ABC']);

        //#3
        array_push($tracking_activities, (factory(Activity::class)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'ticket_id' => null,
            'external_activity_id' => 'ABC',
            'time_tracking_user_id' => $this->tracking_users[3]->id,
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => $note_2->id,
            'utc_started_at' => Carbon::now()->subDays(7)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->subDays(2)->toDateTimeString(),
            'comment' => 'XYZ',
            'tracked' => 600,
            'activity' => 300, // activity_level 50%
        ])));

        $company = factory(Company::class)->create();
        $other_company_hubstaff_integration = $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
        ]);

        //#4
        // this result shouldn't be included for anyone as it's assigned to other company integration
        array_push($tracking_activities, (factory(Activity::class)->create([
            'integration_id' => $other_company_hubstaff_integration->id,
            'user_id' => $this->user->id,
            'project_id' => null,
            'ticket_id' => null,
            'external_activity_id' => 'ABC',
            'time_tracking_user_id' => $this->tracking_users[4]->id,
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
            'utc_started_at' => Carbon::now()->subDays(4)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->subDays(3)->toDateTimeString(),
            'tracked' => 0,
            'activity' => 5,
        ])));

        return $tracking_activities;
    }

    protected function createActivityTimeTracker()
    {
        //#5
        $activity = factory(Activity::class)->create([
            'integration_id' => $this->time_tracker_integration->id,
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'external_activity_id' => 'ABC',
            'time_tracking_user_id' => $this->tracking_users[4]->id,
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
            'utc_started_at' => Carbon::now()->subDays(10)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->subDays(8)->toDateTimeString(),
            'comment' => 'Test abc def',
            'tracked' => 1500,
            'activity' => 0, // activity_level 0%
        ]);

        $this->tracking_activities[] = $activity;

        //#6
        $activity = factory(Activity::class)->create([
            'integration_id' => $this->time_tracker_integration->id,
            'user_id' => $this->users->first()->id,
            'project_id' => null,
            'ticket_id' => null,
            'external_activity_id' => 'ABC',
            'time_tracking_user_id' => $this->tracking_users[4]->id,
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
            'utc_started_at' => Carbon::now()->subDays(7)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->subDays(2)->toDateTimeString(),
            'comment' => 'Supercomment',
            'tracked' => 5,
            'activity' => 50, // activity_level 50%
        ]);

        $this->tracking_activities[] = $activity;
    }
}
