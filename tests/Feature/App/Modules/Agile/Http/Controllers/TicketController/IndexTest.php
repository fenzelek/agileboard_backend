<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;
use Tests\BrowserKitTestCase;

class IndexTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper, ResponseHelper, TestTrait;

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Check response structure
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_assert_response_structure()
    {
        $this->initEnv(RoleType::CLIENT);
        $this->prepareData();
        $this->project->permission->client_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $this->project->permission->save();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url);

        $this->seeJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'project_id',
                    'sprint_id',
                    'sprint_name',
                    'status_id',
                    'ticket_id',
                    'name',
                    'title',
                    'type_id',
                    'assigned_id',
                    'reporter_id',
                    'description',
                    'estimate_time',
                    'scheduled_time_start',
                    'scheduled_time_end',
                    'priority',
                    'hidden',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'activity_permission',
                    'stories' => [
                        'data',
                    ],
                    'assigned_user' => [
                        'data' => [
                            'id',
                            'email',
                            'first_name',
                            'last_name',
                            'avatar',
                            'activated',
                            'deleted',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Return only tickets assigned to user
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_get_only_tickets_assigned_to_user()
    {
        $this->initEnv(RoleType::CLIENT);
        $this->prepareData();
        $this->project->permission->client_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $this->project->permission->save();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url);

        $responseTickets = $this->decodeResponseJson()['data'];
        foreach ($responseTickets as $ticket) {
            $this->assertEquals($this->user->id, $ticket['assigned_id']);
        }
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Return only tickets assigned to user
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_get_only_tickets_assigned_to_user_who_has_developers_permissions()
    {
        $this->initEnv(RoleType::DEVELOPER);
        $this->prepareData();
        $this->project->permission->developer_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $this->project->permission->save();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url);

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(1, $responseTickets);
        foreach ($responseTickets as $ticket) {
            $this->assertEquals($this->user->id, $ticket['assigned_id']);
        }
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Return only tickets not assigned to user
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_get_only_tickets_not_assigned_to_user()
    {
        $this->initEnv(RoleType::CLIENT);
        $this->prepareData();
        $this->project->permission->client_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $this->project->permission->save();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url);

        $responseTickets = $this->decodeResponseJson()['data'];
        foreach ($responseTickets as $ticket) {
            $this->assertNull($ticket['assigned_id']);
        }
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Return those tickets where user is assigned and those where nobody is assigned
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_get_those_tickets_where_user_is_assigned_and_those_where_nobody_is_assigned()
    {
        $this->initEnv(RoleType::CLIENT);
        $this->prepareData();
        $this->project->permission->client_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $this->project->permission->save();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url);

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(2, $responseTickets);
        $this->assertEquals($this->user->id, $responseTickets[0]['assigned_id']);
        $this->assertNull($responseTickets[1]['assigned_id']);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Return only tickets where user is assigned as reporter
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_get_only_tickets_where_user_is_assigned_as_reporters()
    {
        $this->initEnv(RoleType::CLIENT);
        $this->prepareData();
        $this->project->permission->client_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $this->project->permission->save();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url);

        $responseTickets = $this->decodeResponseJson()['data'];
        foreach ($responseTickets as $ticket) {
            $this->assertEquals($this->user->id, $ticket['reporter_id']);
        }
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Return all tickets
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_get_all_tickets()
    {
        $this->initEnv(RoleType::CLIENT);
        $data = $this->prepareData();
        $this->project->permission->client_ticket_show = [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $this->project->permission->save();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url);

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(count($data['ticket']), $responseTickets);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Return no tickets when user hasn't any permissions
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_return_no_tickets_when_user_has_not_any_permissions()
    {
        $this->initEnv(RoleType::CLIENT);
        $this->prepareData();
        $this->project->permission->client_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $this->project->permission->save();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url);

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(0, $responseTickets);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Return error when input data is incorrect
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_validation_error()
    {
        $this->initEnv(RoleType::CLIENT);
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url . '&sprint_id=0&story_id=-1&hidden=2');

        $this->verifyValidationResponse(['story_id', 'hidden']);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Return error when user hasn't permission
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_no_permissions_error()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $url = $this->prepareUrl($project->id, $company->id);

        $this->get($url)->seeStatusCode(401);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Success when user has permission
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_company_admin_has_permissions()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $url = $this->prepareUrl($project->id, $company->id);

        $response = $this->get($url . '&sprint_id=0');
        $response->seeStatusCode(200);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Success while selecting backlog
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_success_selected_backlog()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url . '&sprint_id=0')
            ->seeStatusCode(200)
            ->isJson();

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertEquals(1, count($responseTickets));
        foreach ($data['ticket'][3]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[0][$key]);
        }
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Success while searching by name
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_success_search_name()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url . '&search=search_text')
            ->seeStatusCode(200)
            ->isJson();

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(1, $responseTickets);
        foreach ($data['ticket'][3]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[0][$key]);
        }
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Success while searching by title
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_success_search_title()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url . '&search=' . $data['ticket'][3]->title)
            ->seeStatusCode(200)
            ->isJson();

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(1, $responseTickets);
        foreach ($data['ticket'][3]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[0][$key]);
        }
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Success after verify assigned user stories for selected backlog
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_success_selected_backlog_verify_assigned_users_stories()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $assigned_user = factory(User::class)->create();

        $data['ticket'][3]->assigned_id = $assigned_user->id;
        $data['ticket'][3]->save();
        $data['ticket'][3]->stories()->attach($data['stories'][1]);

        $this->get($url . '&sprint_id=0')
            ->seeStatusCode(200)
            ->isJson();

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(1, $responseTickets);
        foreach ($data['ticket'][3]->fresh()->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[0][$key]);
        }
        $this->assertSame([
            'tracked_summary' => 0,
            'activity_summary' => 0,
            'activity_level' => 0,
            'time_usage' => 0,
        ], $responseTickets[0]['stats']['data']);

        // verify assigned user
        $this->assertEquals(
            $this->getExpectedUserResponse($assigned_user),
            $responseTickets[0]['assigned_user']['data']
        );

        $this->assertCount(1, $responseTickets[0]['stories']['data']);

        $this->assertSame([
            'id' => $data['stories'][1]['id'],
            'project_id' => $data['stories'][1]['project_id'],
            'name' => $data['stories'][1]['name'],
            'color' => $data['stories'][1]['color'],
            'priority' => $data['stories'][1]['priority'],
            'created_at' => $data['stories'][1]['created_at']->toDateTimeString(),
            'updated_at' => $data['stories'][1]['updated_at']->toDateTimeString(),
            'deleted_at' => $data['stories'][1]['deleted_at'],
        ], $responseTickets[0]['stories']['data'][0]);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Success while selecting backlog and stories
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_success_selected_backlog_selected_stories()
    {
        $this->initEnv(RoleType::CLIENT);
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data['ticket'][0]->sprint_id = 0;
        $data['ticket'][0]->save();
        $data['ticket'][3]->stories()->attach($data['stories'][1]);

        $this->get($url . '&sprint_id=0&story_id=' . $data['stories'][1]->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(1, $responseTickets);
        foreach ($data['ticket'][3]->fresh()->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[0][$key]);
        }
        $this->assertTrue(! isset($response_ticket['stats']['data']));

        $this->assertSame([
            'id' => $data['stories'][1]['id'],
            'project_id' => $data['stories'][1]['project_id'],
            'name' => $data['stories'][1]['name'],
            'color' => $data['stories'][1]['color'],
            'priority' => $data['stories'][1]['priority'],
            'created_at' => $data['stories'][1]['created_at']->toDateTimeString(),
            'updated_at' => $data['stories'][1]['updated_at']->toDateTimeString(),
            'deleted_at' => $data['stories'][1]['deleted_at'],
        ], $responseTickets[0]['stories']['data'][0]);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Success while selecting sprint without hidden
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_success_selected_sprint_without_hidden()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->get($url . '&sprint_id=' . $data['sprints'][1]->id . '&hidden=0')
            ->seeStatusCode(200)
            ->isJson();

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertEquals(1, count($responseTickets));

        foreach ($data['ticket'][1]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[0][$key]);
        }

        foreach ($responseTickets as $item) {
            $this->assertSame('test', $item['sprint_name']);
        }
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Success while selecting single story
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_success_selected_single_story()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $data['sprints'][1]->id,
            'priority' => 3,
            'hidden' => 1,
        ]);

        $this->get($url . '&story_id=' . $data['stories'][1]->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(2, $responseTickets);

        foreach ($data['ticket'][1]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[0][$key]);
        }
        $this->assertSame([
            'tracked_summary' => 0,
            'activity_summary' => 0,
            'activity_level' => 0,
            'time_usage' => 0,
        ], $responseTickets[0]['stats']['data']);

        foreach ($data['ticket'][2]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[1][$key]);
        }
        $this->assertSame([
            'tracked_summary' => 0,
            'activity_summary' => 0,
            'activity_level' => 0,
            'time_usage' => 0,
        ], $responseTickets[1]['stats']['data']);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Success while selecting stories
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_success_selected_stories()
    {
        // here
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $data['sprints'][1]->id,
            'priority' => 3,
            'hidden' => 1,
        ]);

        $this->get("{$url}&story_ids[]={$data['stories'][1]->id}&story_ids[]={$data['stories'][0]->id}")
            ->seeStatusCode(200)
            ->isJson();

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(3, $responseTickets);

        foreach ($data['ticket'][1]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[1][$key]);
        }
        $this->assertSame([
            'tracked_summary' => 0,
            'activity_summary' => 0,
            'activity_level' => 0,
            'time_usage' => 0,
        ], $responseTickets[0]['stats']['data']);

        foreach ($data['ticket'][2]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[2][$key]);
        }
        $this->assertSame([
            'tracked_summary' => 0,
            'activity_summary' => 0,
            'activity_level' => 0,
            'time_usage' => 0,
        ], $responseTickets[1]['stats']['data']);
    }

    /**
     * @scenario Ticket Listing
     *      @suit Ticket Listing
     *      @case Success while selecting story when activities exist
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::index
     * @test
     */
    public function index_success_selected_story_when_activities_exist()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $data['sprints'][1]->id,
            'priority' => 3,
            'hidden' => 1,
        ]);

        $data['ticket'][1]->update(['estimate_time' => 1000]);
        $data['ticket'][2]->update(['estimate_time' => 100]);

        // add activities for 1st ticket
        $activity_1 = factory(Activity::class)->create([
            'ticket_id' => $data['ticket'][1]->id,
            'tracked' => 300,
            'activity' => 150,
            'user_id' => 5123,
        ]);

        $activity_2 = factory(Activity::class)->create([
            'ticket_id' => $data['ticket'][1]->id,
            'tracked' => 500,
            'activity' => 100,
            'user_id' => 531231,
        ]);

        // add activities for 2nd ticket
        $activity_3 = factory(Activity::class)->create([
            'ticket_id' => $data['ticket'][2]->id,
            'tracked' => 50,
            'activity' => 30,
            'user_id' => 51230,
        ]);

        $activity_4 = factory(Activity::class)->create([
            'ticket_id' => $data['ticket'][2]->id,
            'tracked' => 90,
            'activity' => 45,
            'user_id' => 17491,
        ]);

        $this->get($url . '&story_id=' . $data['stories'][1]->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseTickets = $this->decodeResponseJson()['data'];
        $this->assertCount(2, $responseTickets);

        foreach ($data['ticket'][1]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[0][$key]);
        }
        $this->assertSame([
            'tracked_summary' => $activity_1->tracked + $activity_2->tracked,
            'activity_summary' => $activity_1->activity + $activity_2->activity,
            'activity_level' => 31.25, // 250 / 800 * 100
            'time_usage' => 80, // 800 / 1000 * 100
        ], $responseTickets[0]['stats']['data']);

        foreach ($data['ticket'][2]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseTickets[1][$key]);
        }
        $this->assertSame([
            'tracked_summary' => $activity_3->tracked + $activity_4->tracked,
            'activity_summary' => $activity_3->activity + $activity_4->activity,
            'activity_level' => 53.57, // 75 / 140 * 100
            'time_usage' => 140, // 140 / 100 * 100
        ], $responseTickets[1]['stats']['data']);
    }

    /**
     * @param $project_id
     * @param $company_id
     *
     * @return string
     */
    private function prepareUrl($project_id, $company_id)
    {
        return "/projects/{$project_id}/tickets?selected_company_id={$company_id}";
    }

    /**
     * @return mixed
     */
    private function prepareData()
    {
        $data['sprints'] [] = $this->createSprint('test', 1, Sprint::INACTIVE);
        $data['sprints'] [] = $this->createSprint('test', 2, Sprint::ACTIVE);

        $data['stories'] [] = factory(Story::class)->create();
        $data['stories'] [] = factory(Story::class)->create([
            'project_id' => $this->project->id,
        ]);

        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $data['sprints'][0]->id,
            'assigned_id' => $this->user->id,
            'name' => 'test',
            'priority' => 1,
            'hidden' => 0,
        ]);

        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $data['sprints'][1]->id,
            'reporter_id' => $this->user->id,
            'name' => 'test',
            'priority' => 2,
            'hidden' => 0,
        ]);
        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $data['sprints'][1]->id,
            'name' => 'test',
            'priority' => 3,
            'hidden' => 1,
        ]);
        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => 0,
            'name' => 'asd search_text asd',
            'priority' => 4,
            'hidden' => 0,
        ]);
        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'assigned_id' => null,
        ]);
        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'reporter_id' => null,
        ]);
        $data['ticket'][0]->stories()->attach($data['stories'][0]);
        $data['ticket'][1]->stories()->attach($data['stories'][1]);
        $data['ticket'][2]->stories()->attach($data['stories'][1]);

        return $data;
    }
}
