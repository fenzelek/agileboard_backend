<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers;

use App\Models\Db\File;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\CreateStatusesEvent;
use App\Modules\Agile\Events\UpdateStatusesEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;
use Illuminate\Support\Facades\Event;

class StatusControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /** @test */
    public function index_validation_error()
    {
        $data = $this->index_prepare_data();

        $response = $this->get('/projects/' . $data['project']->id . '/statuses?selected_company_id=' .
            $data['company']->id . '&tickets=2&sprint_ids[]=string&story_ids[]=string');

        $response->seeStatusCode(422);
        $response->see('general.validation_failed');
        $response->see('tickets');
        $response->see('sprint_ids');
        $response->see('story_ids');
    }

    /** @test */
    public function index_no_permissions_error()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);
        $project = factory(Project::class)->create(['company_id' => $company->id]);

        $this->get('/projects/' . $project->id . '/statuses?selected_company_id=' .
            $company->id . '&status=inactive')->seeStatusCode(401);
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\StatusController::index
     * @test
     */
    public function index_only_statuses_success()
    {
        $data = $this->index_prepare_data();

        $this->get('/projects/' . $data['project']->id . '/statuses?selected_company_id=' .
            $data['company']->id . '&tickets=0')->seeStatusCode(200);

        $response_status = $this->decodeResponseJson()['data'];

        $this->assertSame($data['status'][0]->name, $response_status[0]['name']);
        $this->assertSame($data['status'][0]->project_id, $response_status[0]['project_id']);
        $this->assertSame($data['status'][0]->priority, $response_status[0]['priority']);
        $this->assertSame($data['status'][1]->name, $response_status[1]['name']);
        $this->assertSame($data['status'][1]->project_id, $response_status[1]['project_id']);
        $this->assertSame($data['status'][1]->priority, $response_status[1]['priority']);
        $this->assertSame($data['status'][2]->name, $response_status[2]['name']);
        $this->assertSame($data['status'][2]->project_id, $response_status[2]['project_id']);
        $this->assertSame($data['status'][2]->priority, $response_status[2]['priority']);
    }

    /**
     * @depends index_only_statuses_success
     * @covers \App\Modules\Agile\Http\Controllers\StatusController::index
     * @test
     */
    public function index_statuses_with_tickets_success()
    {
        $data = $this->index_prepare_data();

        $this->get('/projects/' . $data['project']->id . '/statuses?selected_company_id=' .
            $data['company']->id . '&tickets=1')->seeStatusCode(200);

        $response_status = $this->decodeResponseJson()['data'];
        $this->assertCount(3, $response_status);
        $this->assertCount(0, $response_status[0]['tickets']['data']);
        $this->assertCount(1, $response_status[1]['tickets']['data']);
        $this->assertCount(4, $response_status[2]['tickets']['data']);

        // check parent and sub-tickets
        $this->assertArrayHasKey('parent_tickets', $response_status[1]['tickets']['data'][0]);
        $this->assertArrayHasKey('sub_tickets', $response_status[1]['tickets']['data'][0]);
    }

    /**
     * @depends index_only_statuses_success
     * @covers \App\Modules\Agile\Http\Controllers\StatusController::index
     * @test
     */
    public function index_statuses_with_no_tickets_when_user_has_not_permission_to_show_tickets()
    {
        $data = $this->index_prepare_data();
        $data['project']->permission->admin_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $data['project']->permission->save();

        $this->get('/projects/' . $data['project']->id . '/statuses?selected_company_id=' .
            $data['company']->id . '&tickets=1')->seeStatusCode(200);

        $response_status = $this->decodeResponseJson()['data'];
        $this->assertCount(0, $response_status[0]['tickets']['data']);
        $this->assertCount(0, $response_status[1]['tickets']['data']);
        $this->assertCount(0, $response_status[2]['tickets']['data']);
    }

    /**
     * @depends index_only_statuses_success
     * @covers \App\Modules\Agile\Http\Controllers\StatusController::index
     * @test
     */
    public function index_statuses_with_only_those_tickets_where_user_is_a_reporter()
    {
        $data = $this->index_prepare_data();
        $data['project']->permission->admin_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $data['project']->permission->save();

        $this->get('/projects/' . $data['project']->id . '/statuses?selected_company_id=' .
            $data['company']->id . '&tickets=1')->seeStatusCode(200);

        $response_status = $this->decodeResponseJson()['data'];
        $this->assertCount(0, $response_status[0]['tickets']['data']);
        $this->assertCount(1, $response_status[1]['tickets']['data']);
        $this->assertCount(0, $response_status[2]['tickets']['data']);
    }

    /**
     * @depends index_only_statuses_success
     * @covers \App\Modules\Agile\Http\Controllers\StatusController::index
     * @test
     */
    public function index_statuses_with_only_those_tickets_where_user_is_assigned()
    {
        $data = $this->index_prepare_data();
        $data['project']->permission->admin_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $data['project']->permission->save();

        $this->get('/projects/' . $data['project']->id . '/statuses?selected_company_id=' .
            $data['company']->id . '&tickets=1')->seeStatusCode(200);

        $response_status = $this->decodeResponseJson()['data'];
        $this->assertCount(0, $response_status[0]['tickets']['data']);
        $this->assertCount(0, $response_status[1]['tickets']['data']);
        $this->assertCount(1, $response_status[2]['tickets']['data']);
    }

    /**
     * @depends index_only_statuses_success
     * @covers \App\Modules\Agile\Http\Controllers\StatusController::index
     * @test
     */
    public function index_statuses_with_only_those_tickets_where_no_one_is_assigned()
    {
        $data = $this->index_prepare_data();
        $data['project']->permission->admin_ticket_show = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $data['project']->permission->save();

        $this->get('/projects/' . $data['project']->id . '/statuses?selected_company_id=' .
            $data['company']->id . '&tickets=1')->seeStatusCode(200);

        $response_status = $this->decodeResponseJson()['data'];
        $this->assertCount(0, $response_status[0]['tickets']['data']);
        $this->assertCount(0, $response_status[1]['tickets']['data']);
        $this->assertCount(1, $response_status[2]['tickets']['data']);
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\StatusController::index
     * @test
     */
    public function index_filter_story_all_active_sprints_success()
    {
        $data = $this->index_prepare_data();

        $uri = "/projects/{$data['project']->id}/statuses?selected_company_id={$data['company']->id}
        &tickets=1&story_ids[]={$data['story'][1]->id}&story_ids[]={$data['story'][0]->id}";

        $this->get($uri)->seeStatusCode(200);

        $response_status = $this->decodeResponseJson()['data'];

        $this->assertSame($data['status'][0]->name, $response_status[0]['name']);
        $this->assertSame($data['status'][0]->project_id, $response_status[0]['project_id']);
        $this->assertSame($data['status'][0]->priority, $response_status[0]['priority']);
        $this->assertSame(0, count($response_status[0]['tickets']['data']));
        $this->assertSame($data['status'][1]->name, $response_status[1]['name']);
        $this->assertSame($data['status'][1]->project_id, $response_status[1]['project_id']);
        $this->assertSame($data['status'][1]->priority, $response_status[1]['priority']);
        $this->assertSame(1, count($response_status[1]['tickets']['data']));
        $this->assertSame($data['status'][2]->name, $response_status[2]['name']);
        $this->assertSame($data['status'][2]->project_id, $response_status[2]['project_id']);
        $this->assertSame($data['status'][2]->priority, $response_status[2]['priority']);
        $this->assertSame(2, count($response_status[2]['tickets']['data']));
        $this->assertSame($data['ticket'][3]->id, $response_status[2]['tickets']['data'][0]['id']);
        $this->assertEquals(
            $this->getExpectedUserResponse($data['users'][2]),
            $response_status[2]['tickets']['data'][0]['assigned_user']['data']
        );
        $this->assertSame(17, $response_status[2]['tickets']['data'][0]['comments_count']);
        $this->assertSame(9, $response_status[2]['tickets']['data'][0]['files_count']);
        $this->assertSame(1, count($response_status[2]['tickets']['data'][0]['stories']['data']));
        $this->assertSame(
            $data['story'][1]->id,
            $response_status[2]['tickets']['data'][0]['stories']['data'][0]['id']
        );
    }

    // here

    /** @test */
    public function index_filter_sprints_success()
    {
        $data = $this->index_prepare_data();

        $uri = "/projects/{$data['project']->id}/statuses?selected_company_id={$data['company']->id}
        &tickets=1&sprint_ids[]={$data['sprint'][2]->id}&sprint_ids[]={$data['sprint'][1]->id}";

        $this->get($uri)->seeStatusCode(200);

        $response_status = $this->decodeResponseJson()['data'];

        $this->assertSame($data['status'][0]->name, $response_status[0]['name']);
        $this->assertSame($data['status'][0]->project_id, $response_status[0]['project_id']);
        $this->assertSame($data['status'][0]->priority, $response_status[0]['priority']);
        $this->assertSame(0, count($response_status[0]['tickets']['data']));
        $this->assertSame($data['status'][1]->name, $response_status[1]['name']);
        $this->assertSame($data['status'][1]->project_id, $response_status[1]['project_id']);
        $this->assertSame($data['status'][1]->priority, $response_status[1]['priority']);
        $this->assertSame(1, count($response_status[1]['tickets']['data']));
        $this->assertSame($data['status'][2]->name, $response_status[2]['name']);
        $this->assertSame($data['status'][2]->project_id, $response_status[2]['project_id']);
        $this->assertSame($data['status'][2]->priority, $response_status[2]['priority']);
        $this->assertSame(4, count($response_status[2]['tickets']['data']));
        $this->assertSame($data['ticket'][3]->id, $response_status[2]['tickets']['data'][0]['id']);
        $this->assertSame($data['ticket'][2]->id, $response_status[2]['tickets']['data'][1]['id']);

        $this->assertEquals(
            $this->getExpectedUserResponse($data['users'][2]),
            $response_status[2]['tickets']['data'][0]['assigned_user']['data']
        );
        $this->assertSame(17, $response_status[2]['tickets']['data'][0]['comments_count']);
        $this->assertSame(9, $response_status[2]['tickets']['data'][0]['files_count']);
        $this->assertEquals(
            $this->getExpectedUserResponse($data['users'][1]),
            $response_status[2]['tickets']['data'][1]['assigned_user']['data']
        );
        $this->assertSame(0, $response_status[2]['tickets']['data'][1]['comments_count']);
        $this->assertSame(0, $response_status[2]['tickets']['data'][1]['files_count']);
    }

    /** @test */
    public function index_filter_backlog_success()
    {
        $data = $this->index_prepare_data();

        $this->get('/projects/' . $data['project']->id . '/statuses?selected_company_id=' .
            $data['company']->id . '&tickets=1&backlog=1')
            ->seeStatusCode(200);

        $response_status = $this->decodeResponseJson()['data'];

        $this->assertSame($data['status'][0]->name, $response_status[0]['name']);
        $this->assertSame($data['status'][0]->project_id, $response_status[0]['project_id']);
        $this->assertSame($data['status'][0]->priority, $response_status[0]['priority']);
        $this->assertSame(0, count($response_status[0]['tickets']['data']));
        $this->assertSame($data['status'][1]->name, $response_status[1]['name']);
        $this->assertSame($data['status'][1]->project_id, $response_status[1]['project_id']);
        $this->assertSame($data['status'][1]->priority, $response_status[1]['priority']);
        $this->assertSame(1, count($response_status[1]['tickets']['data']));
        $this->assertSame($data['ticket'][5]->id, $response_status[1]['tickets']['data'][0]['id']);
        $this->assertSame($data['status'][2]->name, $response_status[2]['name']);
        $this->assertSame($data['status'][2]->project_id, $response_status[2]['project_id']);
        $this->assertSame($data['status'][2]->priority, $response_status[2]['priority']);
        $this->assertSame(0, count($response_status[2]['tickets']['data']));
    }

    /** @test */
    public function store_it_returns_validation_error_without_data()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $this->post(
            '/projects/' . $project->id . '/statuses?selected_company_id=' . $company->id,
            []
        );

        $this->verifyValidationResponse(['statuses']);
    }

    /** @test */
    public function store_it_returns_validation_error_name_empty()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $this->post(
            '/projects/' . $project->id . '/statuses?selected_company_id=' . $company->id,
            ['statuses' => [['name' => ''], ['name' => 'asdsada']]]
        );

        $this->verifyValidationResponse(['statuses.0.name']);
    }

    /** @test */
    public function store_success_response()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Event::fake();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $this->post(
            '/projects/' . $project->id . '/statuses?selected_company_id=' . $company->id,
            ['statuses' => [['name' => 'test1'], ['name' => 'test2']]]
        )
            ->seeStatusCode(201);

        Event::assertDispatched(CreateStatusesEvent::class, function ($e) use ($project) {
            if ($e->project->id == $project->id) {
                return true;
            }
        });

        $response_status = $this->decodeResponseJson()['data'];

        $this->assertSame('test1', $response_status[0]['name']);
        $this->assertSame($project->id, $response_status[0]['project_id']);
        $this->assertSame(1, $response_status[0]['priority']);
        $this->assertSame($now->toDateTimeString(), $response_status[0]['created_at']);
        $this->assertSame($now->toDateTimeString(), $response_status[0]['updated_at']);
        $this->assertSame('test2', $response_status[1]['name']);
        $this->assertSame($project->id, $response_status[1]['project_id']);
        $this->assertSame(2, $response_status[1]['priority']);
        $this->assertSame($now->toDateTimeString(), $response_status[1]['created_at']);
        $this->assertSame($now->toDateTimeString(), $response_status[1]['updated_at']);
    }

    /** @test */
    public function store_success_db()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $before_statuses = Status::count();

        $this->post(
            '/projects/' . $project->id . '/statuses?selected_company_id=' . $company->id,
            ['statuses' => [['name' => 'test1'], ['name' => 'test2']]]
        )
            ->seeStatusCode(201);

        $count = Status::count();
        $this->assertEquals($before_statuses + 2, $count);

        $statuses = Status::all();

        $this->assertSame('test1', $statuses[$count - 2]->name);
        $this->assertSame($project->id, $statuses[$count - 2]->project_id);
        $this->assertSame(1, $statuses[$count - 2]->priority);
        $this->assertSame(
            $now->toDateTimeString(),
            $statuses[$count - 2]->created_at->toDateTimeString()
        );
        $this->assertSame(
            $now->toDateTimeString(),
            $statuses[$count - 2]->updated_at->toDateTimeString()
        );
        $this->assertSame('test2', $statuses[$count - 1]->name);
        $this->assertSame($project->id, $statuses[$count - 1]->project_id);
        $this->assertSame(2, $statuses[$count - 1]->priority);
        $this->assertSame(
            $now->toDateTimeString(),
            $statuses[$count - 1]->created_at->toDateTimeString()
        );
        $this->assertSame(
            $now->toDateTimeString(),
            $statuses[$count - 1]->updated_at->toDateTimeString()
        );
    }

    /** @test */
    public function update_it_returns_validation_error_without_data()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $this->put(
            '/projects/' . $project->id . '/statuses/?selected_company_id=' . $company->id,
            []
        );

        $this->verifyValidationResponse(['statuses']);
    }

    /** @test */
    public function update_it_returns_validation_error_empty_values()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $status_1 = factory(Status::class)->create(['project_id' => $project->id, 'priority' => 1]);

        $this->put(
            '/projects/' . $project->id . '/statuses/?selected_company_id=' . $company->id,
            [
                'statuses' => [
                    ['name' => 'test1'],
                    ['id' => -1, 'delete' => '2'],
                    ['id' => $status_1->id, 'delete' => '1'],
                    ['id' => $status_1->id, 'delete' => '1', 'new_status' => 0],
                    ['id' => $status_1->id, 'delete' => '0', 'name' => ''],
                ],
            ]
        );

        $this->verifyValidationResponse([
            'statuses.0.delete',
            'statuses.1.id',
            'statuses.1.delete',
            'statuses.2.new_status',
            'statuses.3.new_status',
            'statuses.4.name',
        ]);
    }

    /** @test */
    public function update_success_db()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $status_1 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'name' => 'test1',
        ]);
        $status_2 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 2,
            'name' => 'test2',
        ]);
        $status_3 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 3,
            'name' => 'test3',
        ]);
        $status_4 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 4,
            'name' => 'test4',
        ]);

        $ticket = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'status_id' => $status_2->id,
        ]);

        $before_statuses = Status::count();

        $this->put(
            '/projects/' . $project->id . '/statuses/?selected_company_id=' . $company->id,
            [
                'statuses' => [
                    ['id' => $status_3->id, 'delete' => '0', 'name' => 'nowy_test3'],
                    ['id' => $status_2->id, 'delete' => '1', 'new_status' => $status_1->id],
                    ['id' => $status_4->id, 'delete' => '0', 'name' => 'nowy_test4'],
                    ['id' => 0, 'delete' => '0', 'name' => 'nowy_test5'],
                    ['id' => $status_1->id, 'delete' => '0', 'name' => 'nowy_test1'],
                ],
            ]
        )->seeStatusCode(200);

        $count = Status::count();
        $this->assertEquals($before_statuses, $count);

        $statuses = Status::all();

        $ticket = $ticket->fresh();

        $this->assertSame($status_1->id, $ticket->status_id);

        $this->assertSame('nowy_test1', $statuses[$count - 4]->name);
        $this->assertSame($project->id, $statuses[$count - 4]->project_id);
        $this->assertSame(4, $statuses[$count - 4]->priority);
        $this->assertSame('nowy_test3', $statuses[$count - 3]->name);
        $this->assertSame($project->id, $statuses[$count - 3]->project_id);
        $this->assertSame(1, $statuses[$count - 3]->priority);
        $this->assertSame('nowy_test4', $statuses[$count - 2]->name);
        $this->assertSame($project->id, $statuses[$count - 2]->project_id);
        $this->assertSame(2, $statuses[$count - 2]->priority);
        $this->assertSame('nowy_test5', $statuses[$count - 1]->name);
        $this->assertSame($project->id, $statuses[$count - 1]->project_id);
        $this->assertSame(3, $statuses[$count - 1]->priority);
    }

    /** @test */
    public function update_success_response()
    {
        Event::fake();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $status_1 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'name' => 'test1',
        ]);
        $status_2 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 2,
            'name' => 'test2',
        ]);
        $status_3 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 3,
            'name' => 'test3',
        ]);
        $status_4 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 4,
            'name' => 'test4',
        ]);

        $this->put(
            '/projects/' . $project->id . '/statuses/?selected_company_id=' . $company->id,
            [
                'statuses' => [
                    ['id' => $status_3->id, 'delete' => '0', 'name' => 'nowy_test3'],
                    ['id' => $status_2->id, 'delete' => '1', 'new_status' => $status_1->id],
                    ['id' => $status_4->id, 'delete' => '0', 'name' => 'nowy_test4'],
                    ['id' => 0, 'delete' => '0', 'name' => 'nowy_test5'],
                    ['id' => $status_1->id, 'delete' => '0', 'name' => 'nowy_test1'],
                ],
            ]
        )->seeStatusCode(200);

        Event::assertDispatched(UpdateStatusesEvent::class, function ($e) use ($project) {
            if ($e->project->id == $project->id) {
                return true;
            }
        });

        $response_status = $this->decodeResponseJson()['data'];

        $this->assertSame('nowy_test1', $response_status[0]['name']);
        $this->assertSame($project->id, $response_status[0]['project_id']);
        $this->assertSame(4, $response_status[0]['priority']);
        $this->assertSame('nowy_test3', $response_status[1]['name']);
        $this->assertSame($project->id, $response_status[1]['project_id']);
        $this->assertSame(1, $response_status[1]['priority']);
        $this->assertSame('nowy_test4', $response_status[2]['name']);
        $this->assertSame($project->id, $response_status[2]['project_id']);
        $this->assertSame(2, $response_status[2]['priority']);
        $this->assertSame('nowy_test5', $response_status[3]['name']);
        $this->assertSame($project->id, $response_status[3]['project_id']);
        $this->assertSame(3, $response_status[3]['priority']);
    }

    protected function index_prepare_data()
    {
        $return['users'] = factory(User::class, 3)->create();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $return['company'] = $this->createCompanyWithRole(RoleType::ADMIN);
        $return['project'] =
            factory(Project::class)->create(['company_id' => $return['company']->id]);
        $return['project']->users()->attach($this->user, ['role_id' => Role::findByName(RoleType::ADMIN)->id]);

        $return['status'] [] = factory(Status::class)->create([
            'project_id' => $return['project']->id,
            'priority' => 1,
        ]);
        $return['status'] [] = factory(Status::class)->create([
            'project_id' => $return['project']->id,
            'priority' => 2,
        ]);
        $return['status'] [] = factory(Status::class)->create([
            'project_id' => $return['project']->id,
            'priority' => 3,
        ]);

        $return['story'] [] =
            factory(Story::class)->create(['project_id' => $return['project']->id]);
        $return['story'] [] =
            factory(Story::class)->create(['project_id' => $return['project']->id]);

        $return['sprint'] [] = factory(Sprint::class)->create([
            'project_id' => $return['project']->id,
            'status' => Sprint::CLOSED,
        ]);
        $return['sprint'] [] = factory(Sprint::class)->create([
            'project_id' => $return['project']->id,
            'status' => Sprint::ACTIVE,
        ]);
        $return['sprint'] [] = factory(Sprint::class)->create([
            'project_id' => $return['project']->id,
            'status' => Sprint::ACTIVE,
        ]);
        $return['sprint'] [] = factory(Sprint::class)->create([
            'project_id' => $return['project']->id,
            'status' => Sprint::PAUSED,
        ]);

        $return['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $return['project']->id,
            'status_id' => $return['status'][0]->id,
            'sprint_id' => $return['sprint'][0]->id,
        ]);
        $return['ticket'][0]->stories()->attach($return['story'][1]);
        $return['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $return['project']->id,
            'status_id' => $return['status'][1]->id,
            'sprint_id' => $return['sprint'][1]->id,
            'assigned_id' => $return['users'][0]->id,
            'reporter_id' => $this->user->id,
        ]);
        $return['ticket'][1]->stories()->attach($return['story'][0]);
        $return['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $return['project']->id,
            'status_id' => $return['status'][2]->id,
            'sprint_id' => $return['sprint'][2]->id,
            'priority' => 3,
            'assigned_id' => $return['users'][1]->id,
        ]);
        $return['ticket'][2]->stories()->attach($return['story'][0]);
        $return['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $return['project']->id,
            'status_id' => $return['status'][2]->id,
            'sprint_id' => $return['sprint'][2]->id,
            'priority' => 2,
            'assigned_id' => $return['users'][2]->id,
        ]);
        $return['ticket'][3]->stories()->attach($return['story'][1]);
        $return['ticket'][3]->comments()->saveMany(factory(TicketComment::class, 17)->make());
        $return['ticket'][3]->files()->saveMany(factory(File::class, 9)->make());

        $return['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $return['project']->id,
            'status_id' => $return['status'][2]->id,
            'sprint_id' => $return['sprint'][2]->id,
            'hidden' => true,
            'assigned_id' => null,
        ]);
        $return['ticket'][4]->stories()->attach($return['story'][1]);

        $return['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $return['project']->id,
            'status_id' => $return['status'][1]->id,
            'sprint_id' => 0,
            'assigned_id' => $return['users'][0]->id,
        ]);
        $return['ticket'][5]->stories()->attach($return['story'][0]);

        $return['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $return['project']->id,
            'status_id' => $return['status'][2]->id,
            'sprint_id' => $return['sprint'][2]->id,
            'hidden' => false,
            'assigned_id' => $this->user->id,
        ]);

        $return['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $return['project']->id,
            'status_id' => $return['status'][2]->id,
            'sprint_id' => $return['sprint'][2]->id,
            'assigned_id' => null,
        ]);

        $return['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $return['project']->id,
            'status_id' => $return['status'][2]->id,
            'sprint_id' => $return['sprint'][3]->id, // paused sprint
            'assigned_id' => null,
        ]);

        return $return;
    }
}
