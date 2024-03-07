<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\SprintController;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class IndexTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    const VERIFY_ALL_STATUS = 'verify-all';
    const VERIFY_MIN_STATUS = 'verify-min';
    const VERIFY_NO_STATUS = 'verify-no';
    const VERIFY_CLIENT_STATUS = 'verify-client';
    const NO_VERIFY = 'no-verify';

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function validation_error()
    {
        $data = $this->prepare_data();

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id);

        $this->verifyValidationResponse(['status']);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function no_permissions_error()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);
        $project = factory(Project::class)->create(['company_id' => $company->id]);

        $this->get('/projects/' . $project->id . '/sprints?selected_company_id=' .
            $company->id . '&status=inactive')->seeStatusCode(401);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function check_response_structure()
    {
        $data = $this->prepare_data();

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=inactive')->seeStatusCode(200);

        $this->seeJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'project_id',
                    'name',
                    'status',
                    'priority',
                    'locked',
                    'planned_activation',
                    'planned_closing',
                    'activated_at',
                    'paused_at',
                    'resumed_at',
                    'closed_at',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function success_inactive()
    {
        $data = $this->prepare_data();

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=inactive')->seeStatusCode(200);

        $this->checkIt($data['sprints'], 0, 1, self::VERIFY_NO_STATUS);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function success_active()
    {
        $data = $this->prepare_data();

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&stats=min')->seeStatusCode(200);

        $this->checkIt($data['sprints'], 2, 3, self::VERIFY_MIN_STATUS);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function success_active_by_client_hidden_timetracking()
    {
        $data = $this->prepare_data(RoleType::CLIENT);

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&stats=all')->seeStatusCode(200);

        $this->checkIt($data['sprints'], 2, 3, self::VERIFY_CLIENT_STATUS);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function success_active_by_client_visible_timetracking()
    {
        $data = $this->prepare_data(RoleType::CLIENT);

        $data['project']->time_tracking_visible_for_clients = true;
        $data['project']->save();

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&stats=all')->seeStatusCode(200);

        $this->checkIt($data['sprints'], 2, 3);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function success_active_with_tickets_and_activities_in_progress_disabled()
    {
        $data = $this->prepare_data();

        // create tickets for sprints
        $ticket_1 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'estimate_time' => 500,
            'project_id' => $data['project']->id,
        ]);
        $ticket_2 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'estimate_time' => 800,
            'project_id' => $data['project']->id,
        ]);
        $ticket_3 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'estimate_time' => 400,
            'project_id' => $data['project']->id,
        ]);
        $ticket_4 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'estimate_time' => 300,
            'project_id' => $data['project']->id,
        ]);
        $ticket_5 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'estimate_time' => 150,
            'project_id' => $data['project']->id,
        ]);

        // add activities for 1st ticket
        $activity_1 = factory(Activity::class)->create([
            'ticket_id' => $ticket_1->id,
            'tracked' => 300,
            'activity' => 150,
            'user_id' => 5123,
        ]);

        $activity_2 = factory(Activity::class)->create([
            'ticket_id' => $ticket_1->id,
            'tracked' => 500,
            'activity' => 100,
            'user_id' => 531231,
        ]);

        // add activities for 3rd ticket
        $activity_3 = factory(Activity::class)->create([
            'ticket_id' => $ticket_3->id,
            'tracked' => 100,
            'activity' => 15,
            'user_id' => 51235,
        ]);

        $activity_4 = factory(Activity::class)->create([
            'ticket_id' => $ticket_3->id,
            'tracked' => 18000,
            'activity' => 16000,
            'user_id' => 533141,
        ]);

        // add activities for 5th ticket
        $activity_5 = factory(Activity::class)->create([
            'ticket_id' => $ticket_5->id,
            'tracked' => 20000,
            'activity' => 16000,
            'user_id' => null,
        ]);

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&stats=all&with_tickets=1')->seeStatusCode(200);

        $this->checkIt($data['sprints'], 2, 3, self::NO_VERIFY);

        $response = $this->decodeResponseJson()['data'];

        //tickets
        $this->assertSame(3, count($response[0]['tickets']['data']));
        $this->assertSame(2, count($response[1]['tickets']['data']));

        //stats
        $tracked_summary = $activity_1->tracked + $activity_2->tracked + $activity_3->tracked + $activity_4->tracked;
        $tickets_estimate_time = $ticket_1->estimate_time + $ticket_2->estimate_time + $ticket_3->estimate_time;
        $this->assertSame([
            'tickets_count' => 3,
            'tickets_estimate_time' => $tickets_estimate_time,
            'tracked_summary' => $tracked_summary,
            'activity_summary' => $activity_1->activity + $activity_2->activity + $activity_3->activity + $activity_4->activity,
            'activity_level' => 86.06, // (150 + 100 + 15 + 16000) / (300 + 500 + 100 + 18000) * 100
            'time_usage' => 1111.76,  // (300 + 500 + 100 + 18000) / (500 + 800+ 400) * 100
            'un_started_estimations' => $ticket_2->estimate_time,
            'expected_final' => $ticket_2->estimate_time + $tracked_summary,
            'estimation_left' => $tickets_estimate_time - $tracked_summary,
            'attitude_to_initial' => 1158.8199999999999,
        ], $response[0]['stats']['data']);

        $tickets_estimate_time = $ticket_4->estimate_time + $ticket_5->estimate_time;
        $this->assertSame([
            'tickets_count' => 2,
            'tickets_estimate_time' => $tickets_estimate_time,
            'tracked_summary' => $activity_5->tracked,
            'activity_summary' => $activity_5->activity,
            'activity_level' => 80, // 16000 / 20000 * 100,
            'time_usage' => 4444.44,  //  20000 / (300 + 150) * 100
            'un_started_estimations' => $ticket_4->estimate_time,
            'expected_final' => $ticket_4->estimate_time + $activity_5->tracked,
            'estimation_left' => $tickets_estimate_time - $activity_5->tracked,
            'attitude_to_initial' => 4511.1099999999997,
        ], $response[1]['stats']['data']);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function success_active_with_tickets_and_activities_in_progress_enabled_with_backlog()
    {
        $data = $this->prepare_data();

        $return['status'] [] = factory(Status::class)->create([
            'project_id' => $data['project']->id,
            'priority' => 1,
        ]);
        $return['status'] [] = factory(Status::class)->create([
            'project_id' => $data['project']->id,
            'priority' => 2,
        ]);

        $data['project']->status_for_calendar_id = $return['status'][1]->id;
        $data['project']->save();

        // create tickets for sprints
        $ticket_1 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'status_id' => $return['status'][0]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 500,
        ]);
        $ticket_2 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'status_id' => $return['status'][1]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 800,
        ]);
        $ticket_3 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'status_id' => $return['status'][1]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 400,
        ]);
        $ticket_4 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'status_id' => $return['status'][0]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 400,
        ]);
        $ticket_5 = factory(Ticket::class)->create([
            'sprint_id' => 0,
            'status_id' => $return['status'][0]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 100,
        ]);

        $other_project = factory(Project::class)->create([
            'company_id' => $data['company']->id,
        ]);

        $ticket_6 = factory(Ticket::class)->create([
            'sprint_id' => 0,
            'project_id' => $other_project->id,
            'estimate_time' => 400,
        ]);

        // add activities for 3rd ticket
        $activity_3 = factory(Activity::class)->create([
            'ticket_id' => $ticket_3->id,
            'tracked' => 100,
            'activity' => 15,
            'user_id' => 51235,
        ]);

        $activity_4 = factory(Activity::class)->create([
            'ticket_id' => $ticket_3->id,
            'tracked' => 18000,
            'activity' => 16000,
            'user_id' => 533141,
        ]);

        // add activities for 4rd ticket
        $activity_5 = factory(Activity::class)->create([
            'ticket_id' => $ticket_4->id,
            'tracked' => 300,
            'activity' => 16000,
            'user_id' => 533141,
        ]);

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&with_backlog=1&stats=all')->seeStatusCode(200);

        $response = $this->decodeResponseJson()['data'];

        $tracked_summary = $activity_3->tracked + $activity_4->tracked + $activity_5->tracked;
        $tickets_estimate_time = $ticket_1->estimate_time + $ticket_2->estimate_time + $ticket_3->estimate_time + $ticket_4->estimate_time;
        $this->assertSame([
            'tickets_count' => 4,
            'tickets_estimate_time' => $tickets_estimate_time,
            'tracked_summary' => $tracked_summary,
            'activity_summary' => $activity_3->activity + $activity_4->activity + $activity_5->activity,
            'activity_level' => 173.99,
            'time_usage' => 876.19,
            'un_started_estimations' => $ticket_1->estimate_time,
            'expected_final' => $ticket_1->estimate_time + $ticket_2->estimate_time + $activity_3->tracked + $activity_4->tracked + $activity_5->tracked,
            'estimation_left' => $tickets_estimate_time - $tracked_summary,
            'attitude_to_initial' => 938.1,
        ], $response[0]['stats']['data']);

        //backlog
        $this->assertSame([
            'tickets_count' => 1,
            'tickets_estimate_time' => 100,
            'tracked_summary' => 0,
            'activity_summary' => 0,
            'activity_level' => 0,
            'time_usage' => 0,
            'un_started_estimations' => 100,
            'expected_final' => 100,
            'estimation_left' => 100,
            'attitude_to_initial' => 100,
        ], $response[2]['stats']['data']);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function success_active_with_tickets_and_activities_in_progress_disabled_filter_story()
    {
        $data = $this->prepare_data();

        $story = factory(Story::class)->create(['project_id' => $data['project']->id]);

        // create tickets for sprints
        $ticket_1 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 500,
        ]);
        $ticket_2 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 800,
        ]);
        $ticket_3 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 400,
        ]);
        $ticket_4 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 300,
        ]);
        $ticket_5 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 150,
        ]);

        $ticket_1->stories()->attach($story);

        // add activities for 1st ticket
        $activity_1 = factory(Activity::class)->create([
            'ticket_id' => $ticket_1->id,
            'tracked' => 300,
            'activity' => 150,
            'user_id' => 5123,
        ]);

        $activity_2 = factory(Activity::class)->create([
            'ticket_id' => $ticket_1->id,
            'tracked' => 500,
            'activity' => 100,
            'user_id' => 531231,
        ]);

        // add activities for 3rd ticket
        $activity_3 = factory(Activity::class)->create([
            'ticket_id' => $ticket_3->id,
            'tracked' => 100,
            'activity' => 15,
            'user_id' => 51235,
        ]);

        $activity_4 = factory(Activity::class)->create([
            'ticket_id' => $ticket_3->id,
            'tracked' => 18000,
            'activity' => 16000,
            'user_id' => 533141,
        ]);

        // add activities for 5th ticket
        $activity_5 = factory(Activity::class)->create([
            'ticket_id' => $ticket_5->id,
            'tracked' => 20000,
            'activity' => 16000,
            'user_id' => null,
        ]);

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&stats=all&story_ids[]=' . $story->id)->seeStatusCode(200);

        $this->checkIt($data['sprints'], 2, 3, self::NO_VERIFY);

        $response = $this->decodeResponseJson()['data'];

        $tracked_summary = $activity_1->tracked + $activity_2->tracked;
        $tickets_estimate_time = $ticket_1->estimate_time;
        $this->assertSame([
            'tickets_count' => 1,
            'tickets_estimate_time' => $tickets_estimate_time,
            'tracked_summary' => $tracked_summary,
            'activity_summary' => $activity_1->activity + $activity_2->activity,
            'activity_level' => 31.25,
            'time_usage' => 160,
            'un_started_estimations' => 0,
            'expected_final' => $tracked_summary,
            'estimation_left' => $tickets_estimate_time - $tracked_summary,
            'attitude_to_initial' => 160,
        ], $response[0]['stats']['data']);

        $this->assertSame([
            'tickets_count' => 0,
            'tickets_estimate_time' => 0,
            'tracked_summary' => 0,
            'activity_summary' => 0,
            'activity_level' => 0,
            'time_usage' => 0,
            'un_started_estimations' => 0,
            'expected_final' => 0,
            'estimation_left' => 0,
            'attitude_to_initial' => 0,
        ], $response[1]['stats']['data']);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function success_with_tickets_filter_by_stories()
    {
        $data = $this->prepare_data();

        $story = factory(Story::class)->create(['project_id' => $data['project']->id, 'name' => 'story_1']);
        $story_2 = factory(Story::class)->create(['project_id' => $data['project']->id, 'name' => 'story_2']);
        $story_3 = factory(Story::class)->create(['project_id' => $data['project']->id, 'name' => 'story_3']);

        // create tickets for sprints
        $ticket_1 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 500,
        ]);
        $ticket_2 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 800,
        ]);
        $ticket_3 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 400,
        ]);
        $ticket_4 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 300,
        ]);
        $ticket_5 = factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
            'estimate_time' => 150,
        ]);

        $ticket_1->stories()->attach($story);
        $ticket_2->stories()->attach($story_2);
        $ticket_4->stories()->attach($story_2);

        $uri = "/projects/{$data['project']->id}/sprints?selected_company_id={$data['company']->id}&status=not-closed&stats=all&story_ids[]={$story->id}&story_ids[]={$story_2->id}&with_tickets=1&with_backlog=1";

        $this->get($uri)->seeStatusCode(200);

        $response = $this->decodeResponseJson()['data'];

        foreach ($response as $sprint) {
            foreach ($sprint['tickets']['data'] as $ticket) {
                foreach ($ticket['stories']['data'] as $story) {
                    $this->assertTrue($story['name'] === 'story_1' || $story['name'] === 'story_2');
                }
            }
        }
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     * @test
     */
    public function statuses_with_no_tickets_when_user_has_not_permission_to_show_tickets()
    {
        $data = $this->prepare_data();
        $data['project']->permission->admin_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $data['project']->permission->save();

        // create tickets for sprints
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
        ]);

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&stats=all&with_tickets=1')
            ->seeStatusCode(200);

        $response = $this->decodeResponseJson()['data'];
        $this->assertCount(0, $response[0]['tickets']['data']);
        $this->assertCount(0, $response[1]['tickets']['data']);
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     * @test
     */
    public function with_all_tickets()
    {
        $data = $this->prepare_data();

        // create tickets for sprints
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
        ]);

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&stats=all&with_tickets=1')
            ->seeStatusCode(200);

        $response = $this->decodeResponseJson()['data'];
        $this->assertCount(3, $response[0]['tickets']['data']);
        $this->assertCount(2, $response[1]['tickets']['data']);
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     * @test
     */
    public function with_those_tickets_where_user_is_reporter()
    {
        $data = $this->prepare_data();
        $data['project']->permission->admin_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $data['project']->permission->save();

        // create tickets for sprints
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
            'reporter_id' => $this->user->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
        ]);

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&stats=all&with_tickets=1')
            ->seeStatusCode(200);

        $response = $this->decodeResponseJson()['data'];
        $this->assertCount(1, $response[0]['tickets']['data']);
        $this->assertCount(0, $response[1]['tickets']['data']);
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     * @test
     */
    public function with_those_tickets_where_user_is_assigned()
    {
        $data = $this->prepare_data();
        $data['project']->permission->admin_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $data['project']->permission->save();

        // create tickets for sprints
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
            'assigned_id' => $this->user->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
        ]);

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&stats=all&with_tickets=1')
            ->seeStatusCode(200);

        $response = $this->decodeResponseJson()['data'];
        $this->assertCount(1, $response[0]['tickets']['data']);
        $this->assertCount(0, $response[1]['tickets']['data']);
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     * @test
     */
    public function with_those_tickets_where_no_one_is_assigned()
    {
        $data = $this->prepare_data();
        $data['project']->permission->admin_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $data['project']->permission->save();

        // create tickets for sprints
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
            'assigned_id' => $this->user->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
            'assigned_id' => null,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][2]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $data['sprints'][3]->id,
            'project_id' => $data['project']->id,
            'assigned_id' => null,
        ]);

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=active&stats=all&with_tickets=1')
            ->seeStatusCode(200);

        $response = $this->decodeResponseJson()['data'];
        $this->assertCount(1, $response[0]['tickets']['data']);
        $this->assertCount(1, $response[1]['tickets']['data']);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function success_closed()
    {
        $data = $this->prepare_data();

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&stats=all&status=closed')->seeStatusCode(200);

        $this->checkIt($data['sprints'], 4, 5);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function success_not_closed()
    {
        $data = $this->prepare_data();

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&stats=all&status=not-closed')->seeStatusCode(200);

        $this->checkIt($data['sprints'], 0, 3);
    }

    /**
     *
     * @Feature Agile
     * @Scenario Get Sprints
     * @Case Include Backlog without Tickets
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::index
     */
    public function index_getBacklogWithoutTickets()
    {
        $data = $this->createSprintWithoutTicket(RoleType::CLIENT);
        //other project ticket
        factory(Ticket::class)->create([
            'sprint_id' => 0,
        ]);

        $this->get('/projects/' . $data['project']->id . '/sprints?selected_company_id=' .
            $data['company']->id . '&status=not-closed&with_tickets=0&with_backlog=1')->seeStatusCode(200);

    }

    protected function createSprintWithoutTicket($role = RoleType::ADMIN)
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $return['company'] = $this->createCompanyWithRole($role);
        $return['project'] = factory(Project::class)->create([
            'company_id' => $return['company']->id,
            'time_tracking_visible_for_clients' => 0,
        ]);
        $return['project']->users()->attach($this->user, ['role_id' => Role::findByName($role)->id]);

        return $return;
    }
    protected function prepare_data($role = RoleType::ADMIN)
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $return['company'] = $this->createCompanyWithRole($role);
        $return['project'] = factory(Project::class)->create([
            'company_id' => $return['company']->id,
            'time_tracking_visible_for_clients' => 0,
        ]);
        $return['project']->users()
            ->attach($this->user, ['role_id' => Role::findByName($role)->id]);

        $return['sprints'] [] = factory(Sprint::class)->create([
            'project_id' => $return['project']->id,
            'priority' => 1,
            'name' => 'test',
            'status' => Sprint::INACTIVE,
        ]);
        $return['sprints'] [] = factory(Sprint::class)->create([
            'project_id' => $return['project']->id,
            'priority' => 2,
            'name' => 'test',
            'status' => Sprint::INACTIVE,
        ]);
        $return['sprints'] [] = factory(Sprint::class)->create([
            'project_id' => $return['project']->id,
            'priority' => 3,
            'name' => 'test',
            'status' => Sprint::ACTIVE,
        ]);
        $return['sprints'] [] = factory(Sprint::class)->create([
            'project_id' => $return['project']->id,
            'priority' => 4,
            'name' => 'test',
            'status' => Sprint::ACTIVE,
        ]);
        $return['sprints'] [] = factory(Sprint::class)->create([
            'project_id' => $return['project']->id,
            'priority' => 5,
            'name' => 'test',
            'status' => Sprint::CLOSED,
        ]);
        $return['sprints'] [] = factory(Sprint::class)->create([
            'project_id' => $return['project']->id,
            'priority' => 6,
            'name' => 'test',
            'status' => Sprint::CLOSED,
        ]);

        return $return;
    }

    protected function checkIt($sprints, $start, $stop, $verify_stats = self::VERIFY_ALL_STATUS)
    {
        $response_sprint = $this->decodeResponseJson()['data'];

        $this->assertSame($stop - $start + 1, count($response_sprint));

        $j = 0;
        for ($i = $start; $i < $stop; ++$i, ++$j) {
            $this->assertSame($sprints[$i]['name'], $response_sprint[$j]['name']);
            $this->assertSame($sprints[$i]['project_id'], $response_sprint[$j]['project_id']);
            $this->assertSame($sprints[$i]['status'], $response_sprint[$j]['status']);
            $this->assertSame($sprints[$i]['priority'], $response_sprint[$j]['priority']);
            $this->assertSame(
                $sprints[$i]['created_at']->toDateTimeString(),
                $response_sprint[$j]['created_at']
            );
            $this->assertSame(
                $sprints[$i]['updated_at']->toDateTimeString(),
                $response_sprint[$j]['updated_at']
            );
            if ($verify_stats == self::VERIFY_ALL_STATUS) {
                $this->assertSame([
                    'tickets_count' => 0,
                    'tickets_estimate_time' => 0,
                    'tracked_summary' => 0,
                    'activity_summary' => 0,
                    'activity_level' => 0,
                    'time_usage' => 0,
                    'un_started_estimations' => 0,
                    'expected_final' => 0,
                    'estimation_left' => 0,
                    'attitude_to_initial' => 0,
                ], $response_sprint[$j]['stats']['data']);
            } elseif ($verify_stats == self::VERIFY_MIN_STATUS) {
                $this->assertSame([
                    'tickets_count' => 0,
                    'tickets_estimate_time' => 0,
                    'tracked_summary' => 0,
                    'activity_summary' => 0,
                    'activity_level' => 0,
                    'time_usage' => 0,
                ], $response_sprint[$j]['stats']['data']);
            } elseif ($verify_stats == self::VERIFY_CLIENT_STATUS) {
                $this->assertSame([
                    'tickets_count' => 0,
                    'tickets_estimate_time' => 0,
                ], $response_sprint[$j]['stats']['data']);
            } elseif ($verify_stats == self::VERIFY_NO_STATUS) {
                $this->assertTrue(! isset($response_sprint[$j]['stats']['data']));
            }
        }
    }
}
