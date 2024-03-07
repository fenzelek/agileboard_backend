<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Status;
use App\Models\Db\Ticket;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use App\Models\Db\Project;
use App\Models\Db\Company;
use App\Helpers\ErrorCode;
use Carbon\Carbon;
use App\Models\Db\Integration\TimeTracking\User as TimeTrackingUser;
use Illuminate\Support\Collection;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class ShowTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    protected $company;
    protected $new_company;
    protected $now;
    protected $project;
    protected $developer;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->developer = factory(User::class)->create();

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $this->assignUsersToCompany($this->developer->get(), $this->company, RoleType::DEVELOPER);

        $this->new_company = factory(Company::class)->create();
        $this->project = factory(Project::class)->create([
            'status_for_calendar_id' => 1,
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function show_it_shows_project_to_admin_with_success()
    {
        $this->get('/projects/' . $this->project->id .
            '/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200);
        $response_data = $this->response->getData()->data;

        $this->verifyShowResponse($response_data);
    }

    /** @test */
    public function show_it_shows_project_to_admin_with_detailed_data_with_success()
    {
        $this->setProjectRole($this->project, RoleType::ADMIN);
        $users = factory(User::class, 5)->create();
        $tracking_users = factory(TimeTrackingUser::class, 5)->create(['user_id' => null]);

        list($tickets, $status_todo, $status_in_progress) = $this->createTickets($users);

        $other_tickets = factory(Ticket::class, 2)->create(
            ['project_id' => factory(Project::class)->create()->id, 'status_id' => $status_in_progress->id]
        );

        $tt_tracking_activities = $this->createTimeTrackingActivity($users[0], $tickets[0]);
        $tracking_activities = $this->createTimeTrackingActivities($tracking_users, $users, $tickets, $other_tickets);
        $tracking_activities->push($tt_tracking_activities);

        $this->get('/projects/' . $this->project->id .
            '/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200);
        $response_data = $this->decodeResponseJson()['data'];

        $project_activities = $tracking_activities->filter(function ($activity) {
            return $activity->project_id == $this->project->id;
        });
        $stats = $response_data['stats']['data'];
        $stats = (object) $stats;

        $this->assertSame($tickets->sum('estimate_time'), $stats->total_estimate_time);
        $this->assertSame($tickets->reject(function ($ticket) use ($status_todo) {
            return $ticket->status_id == $status_todo->id;
        })->sum('estimate_time'), $stats->non_todo_estimate_time);
        $this->assertSame(3, $stats->not_estimated_tickets_count);
        $this->assertSame(3, $stats->not_assigned_tickets_count);
        $this->assertSame($project_activities->sum('tracked'), $stats->tracked);
        $this->assertSame($project_activities->sum('activity'), $stats->activity);
        $this->assertEquals($this->calculateActivityLevel($project_activities->sum('tracked'), $project_activities->sum('activity')), $stats->activity_level);

        $expected_time_tracking = $this->getExpectedSummary($tracking_activities, $tracking_users);
        $this->assertEquals($expected_time_tracking, (array) $stats->time_tracking_summary['data']);
    }

    /** @test */
    public function show_it_shows_project_to_developer_with_detailed_data_with_success()
    {
        $this->setProjectRole($this->project, RoleType::DEVELOPER);
        $users = factory(User::class, 5)->create();
        $tracking_users = factory(TimeTrackingUser::class, 5)->create(['user_id' => null]);

        list($tickets, $status_todo, $status_in_progress) = $this->createTickets($users);

        $other_tickets = factory(Ticket::class, 2)->create(
            ['project_id' => factory(Project::class)->create()->id, 'status_id' => $status_in_progress->id]
        );
        $tt_tracking_activities = $this->createTimeTrackingActivity($users[0], $tickets[0]);
        $tracking_activities = $this->createTimeTrackingActivities($tracking_users, $users, $tickets, $other_tickets);
        $tracking_activities->push($tt_tracking_activities);

        $this->get('/projects/' . $this->project->id .
            '/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200);
        $response_data = $this->decodeResponseJson()['data'];

        $project_activities = $tracking_activities->filter(function ($activity) {
            return $activity->project_id == $this->project->id;
        });

        $user_activities = $project_activities->filter(function ($activity) {
            return $activity->user_id == $this->user->id;
        });
        $stats = $response_data['stats']['data'];
        $stats = (object) $stats;
        $this->assertSame($tickets->where('assigned_id', $this->user->id)->sum('estimate_time'), $stats->total_estimate_time);

        $this->assertSame($tickets->where('assigned_id', $this->user->id)->reject(function ($ticket) use ($status_todo) {
            return $ticket->status_id == $status_todo->id;
        })->sum('estimate_time'), $stats->non_todo_estimate_time);
        $this->assertSame(1, $stats->not_estimated_tickets_count);
        $this->assertSame(3, $stats->not_assigned_tickets_count);

        $this->assertSame($user_activities->sum('tracked'), $stats->tracked);

        $this->assertSame($user_activities->sum('activity'), $stats->activity);
        $this->assertEquals($this->calculateActivityLevel($user_activities->sum('tracked'), $user_activities->sum('activity')), $stats->activity_level);

        // for non-admin only 1st entry will be really displayed - notice [0] at the end
        $expected_time_tracking = [$this->getExpectedSummary($tracking_activities, $tracking_users)[0]];

        $this->assertEquals($expected_time_tracking, (array) $stats->time_tracking_summary['data']);
    }

    /** @test */
    public function show_it_shows_project_to_admin_with_success_and_editable_to_false_when_it_has_tickets()
    {
        // create soft-deleted ticket for project
        $ticket = factory(Ticket::class)->create(['project_id' => $this->project->id]);
        $ticket->delete();

        $this->get('/projects/' . $this->project->id .
            '/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200);

        $response_data = $this->response->getData()->data;

        $this->verifyShowResponse($response_data, false, false);
    }

    /** @test */
    public function show_it_shows_closed_project_to_admin_with_success()
    {
        // close project
        $this->project->closed_at = $this->now->toDateTimeString();
        $this->project->save();

        $this->get('/projects/' . $this->project->id .
            '/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200);
        $response_data = $this->response->getData()->data;

        $this->verifyShowResponse($response_data, true);
    }

    /** @test */
    public function show_admin_want_project_not_in_company_get_error()
    {
        // change company where project belongs to
        $this->project->company_id = $this->new_company->id;
        $this->project->save();

        $this->get('/projects/' . $this->project->id .
            '/?selected_company_id=' . $this->company->id)
            ->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function show_it_shows_project_to_developer_in_project_with_success()
    {
        // add developer to project
        $this->project->users()->attach($this->developer);
        $this->be($this->developer);

        $this->get('/projects/' . $this->project->id .
            '/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200);
        $response_data = $this->response->getData()->data;

        $this->assertSame($this->project->id, $response_data->id);
        $this->assertSame($this->project->name, $response_data->name);
        $this->assertSame($this->project->short_name, $response_data->short_name);
        $this->assertEquals($this->project->time_tracking_visible_for_clients, $response_data->time_tracking_visible_for_clients);
        $this->assertEquals($this->project->language, $response_data->language);
        $this->assertSame($this->project->color, $response_data->color);
        $this->assertSame($this->company->id, $response_data->company_id);
        $this->assertSame($this->now->toDateTimeString(), $response_data->created_at);
        $this->assertSame($this->now->toDateTimeString(), $response_data->updated_at);
        $this->assertNull($response_data->deleted_at);
        $this->assertNull($response_data->closed_at);
        $this->assertArrayNotHasKey('time_tracking_summary', (array) $response_data);
    }

    /** @test */
    public function show_it_shows_project_to_developer_not_in_project_with_error()
    {
        $new_project = factory(Project::class)->create([
            'company_id' => $this->company->id,
        ]);
        $new_project->users()->attach($this->developer);
        $this->be($this->developer);

        $this->get('/projects/' . $this->project->id .
            '/?selected_company_id=' . $this->company->id)
            ->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    protected function createTickets($users)
    {
        $status_todo = factory(Status::class)->create([
            'project_id' => $this->project->id,
            'name' => 'TODO',
            'priority' => 1,
        ]);

        $status_in_progress = factory(Status::class)->create([
            'project_id' => $this->project->id,
            'name' => 'IN PROGRESS',
            'priority' => 2,
        ]);

        $status_done = factory(Status::class)->create([
            'project_id' => $this->project->id,
            'name' => 'DONE',
            'priority' => 3,
        ]);

        $tickets = factory(Ticket::class, 6)->create([
            'project_id' => $this->project->id,
            'estimate_time' => 0,
            'status_id' => $status_in_progress->id,
            'assigned_id' => null,
        ]);

        $tickets[0]->update(['estimate_time' => 3600, 'status_id' => $status_todo->id, 'assigned_id' => $this->user->id]);
        $tickets[1]->update(['estimate_time' => 7200, 'status_id' => $status_in_progress->id]);
        $tickets[2]->update(['estimate_time' => 1800, 'status_id' => $status_done->id, 'assigned_id' => $users[0]->id]);
        $tickets[4]->update(['estimate_time' => 10, 'assigned_id' => $this->user->id]);
        $tickets[4]->update(['estimate_time' => 0, 'assigned_id' => $this->user->id]);

        return [$tickets, $status_todo, $status_in_progress];
    }

    protected function getExpectedSummary(Collection $tracking_activities, Collection $tracking_users)
    {
        return [
            [
                'time_tracking_user_id' => $tracking_activities[4]->time_tracking_user_id,
                'tracked_sum' => ($tracking_activities[4]->tracked + $tracking_activities[5]->tracked) . '',
                'activity_sum' => ($tracking_activities[4]->activity + $tracking_activities[5]->activity) . '',
                'activity_level' => $this->calculateActivityLevel($tracking_activities[4]->tracked + $tracking_activities[5]->tracked, $tracking_activities[4]->activity + $tracking_activities[5]->activity),
                'user_id' => $tracking_activities[4]->user_id,
                'user' => [
                    'data' => $this->getExpectedUserResponse($this->user),
                ],
                'time_tracking_user' => [
                    'data' => $this->getExpectedTimeTrackingUserResponse($tracking_users[3]),
                ],
                'project_id' => $this->project->id,
            ],
            [
                'time_tracking_user_id' => null,
                'tracked_sum' => ($tracking_activities[0]->tracked + $tracking_activities[1]->tracked + $tracking_activities[10]->tracked) . '',
                'activity_sum' => ($tracking_activities[0]->activity + $tracking_activities[1]->activity + $tracking_activities[10]->activity) . '',
                'activity_level' => $this->calculateActivityLevel($tracking_activities[0]->tracked + $tracking_activities[1]->tracked + $tracking_activities[10]->tracked, $tracking_activities[0]->activity + $tracking_activities[1]->activity + $tracking_activities[10]->activity),
                'user_id' => $tracking_activities[0]->user_id,
                'user' => [
                    'data' => $this->getExpectedUserResponse($tracking_activities[0]->user),
                ],
                'time_tracking_user' => [
                    'data' => null,
                ],
                'project_id' => $this->project->id,
            ],
            [
                'time_tracking_user_id' => $tracking_activities[3]->time_tracking_user_id,
                'tracked_sum' => ($tracking_activities[3]->tracked) . '',
                'activity_sum' => ($tracking_activities[3]->activity) . '',
                'activity_level' => $this->calculateActivityLevel($tracking_activities[3]->tracked, $tracking_activities[3]->activity),
                'user_id' => $tracking_activities[3]->user_id,
                'user' => [
                    'data' => null,
                ],
                'time_tracking_user' => [
                    'data' => $this->getExpectedTimeTrackingUserResponse($tracking_users[2]),
                ],
                'project_id' => $this->project->id,
            ],
        ];
    }

    protected function createTimeTrackingActivities(Collection $tracking_users, Collection $users, Collection $tickets, Collection $other_tickets)
    {
        $tracking_activities = factory(Activity::class, 10)->create([
            'user_id' => null,
            'project_id' => null,
            'ticket_id' => null,
            'time_tracking_user_id' => $tracking_users[4]->id + 1000,// non-existing user
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
        ]);

        $tracking_activities[0]->update([
            'time_tracking_user_id' => $tracking_users[0]->id,
            'user_id' => $users[0]->id,
            'ticket_id' => $tickets[0]->id,
            'project_id' => $tickets[0]->project->id,
            'tracked' => 300,
        ]);

        $tracking_activities[1]->update([
            'time_tracking_user_id' => $tracking_users[0]->id,
            'user_id' => $users[0]->id,
            'ticket_id' => $tickets[1]->id,
            'project_id' => $tickets[1]->project->id,
            'tracked' => 200,
        ]);

        $tracking_activities[3]->update([
            'time_tracking_user_id' => $tracking_users[2]->id,
            'user_id' => null,
            'ticket_id' => $tickets[2]->id,
            'project_id' => $tickets[2]->project->id,
            'tracked' => 100,
        ]);

        $tracking_activities[4]->update([
            'time_tracking_user_id' => $tracking_users[3]->id,
            'user_id' => $this->user->id,
            'ticket_id' => $tickets[3]->id,
            'project_id' => $tickets[3]->project->id,
            'tracked' => 5000,
            'activity' => 2000,
        ]);

        $tracking_activities[5]->update([
            'time_tracking_user_id' => $tracking_users[3]->id,
            'user_id' => $this->user->id,
            'ticket_id' => $tickets[4]->id,
            'project_id' => $tickets[4]->project->id,
            'tracked' => 400,
            'activity' => 100,
        ]);

        // ticket in other project
        $tracking_activities[6]->update([
            'time_tracking_user_id' => $tracking_users[3]->id,
            'user_id' => $this->user->id,
            'ticket_id' => $other_tickets[0]->id,
            'project_id' => $other_tickets[0]->project->id,
            'tracked' => 400,
            'activity' => 100,
        ]);

        // ticket in other project
        $tracking_activities[7]->update([
            'time_tracking_user_id' => $tracking_users[3]->id,
            'user_id' => $this->user->id,
            'ticket_id' => $other_tickets[1]->id,
            'project_id' => $other_tickets[1]->project->id,
            'tracked' => 400,
            'activity' => 100,
        ]);

        return $tracking_activities;
    }

    protected function verifyShowResponse($response_data, $closed = false, $editable = true)
    {
        $this->assertSame($this->project->id, $response_data->id);
        $this->assertSame($this->project->name, $response_data->name);
        $this->assertSame($this->project->short_name, $response_data->short_name);
        $this->assertEquals($this->project->time_tracking_visible_for_clients, $response_data->time_tracking_visible_for_clients);
        $this->assertEquals($this->project->status_for_calendar_id, $response_data->status_for_calendar_id);
        $this->assertEquals($this->project->language, $response_data->language);
        $this->assertEquals($this->project->email_notification_enabled, $response_data->email_notification_enabled);
        $this->assertEquals($this->project->slack_notification_enabled, $response_data->slack_notification_enabled);
        $this->assertEquals($this->project->slack_webhook_url, $response_data->slack_webhook_url);
        $this->assertEquals($this->project->slack_channel, $response_data->slack_channel);
        $this->assertEquals($this->project->color, $response_data->color);
        $this->assertEquals($this->project->ticket_scheduled_dates_with_time, $response_data->ticket_scheduled_dates_with_time);
        $this->assertSame($this->company->id, $response_data->company_id);
        $this->assertSame($this->now->toDateTimeString(), $response_data->created_at);
        $this->assertSame($this->now->toDateTimeString(), $response_data->updated_at);
        $this->assertNull($response_data->deleted_at);
        if ($closed) {
            $this->assertSame($this->now->toDateTimeString(), $response_data->closed_at);
        } else {
            $this->assertNull($response_data->closed_at);
        }

        $this->assertSame($editable, $response_data->editable_short_name);
        $this->assertArrayNotHasKey('time_tracking_summary', (array) $response_data);
    }

    private function createTimeTrackingActivity(User $user, $ticket)
    {
        $tracking_activity = factory(Activity::class)->create([
            'user_id' => null,
            'project_id' => null,
            'ticket_id' => null,
            'time_tracking_user_id' => null,
            // non-existing time tracking user set here
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
        ]);

        $tracking_activity->update([
            'time_tracking_user_id' => null,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'project_id' => $ticket->project->id,
            'tracked' => 300,
        ]);

        return $tracking_activity;
    }
}
