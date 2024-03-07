<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController;

use App\Models\Db\ProjectPermission;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Helpers\ErrorCode;
use Carbon\Carbon;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController\CloneTest\TestTrait;
use Tests\TestCase;

class CloneTest extends TestCase
{
    use DatabaseTransactions, TestTrait;

    /** @var Company */
    protected $company;
    /** @var Project */
    protected $project;
    /** @var Collection */
    protected $stories;
    /** @var Carbon */
    protected $now;
    /** @var string */
    private $test_name;
    /** @var string */
    private $test_short_name;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stories = collect();
    }

    public function setUp():void
    {
        parent::setUp();
        $this->mockTime('2019-07-07 10:10:10');

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->createCompany();
        $this->createBaseProject();

        $this->test_name = 'First cloned project';
        $this->test_short_name = 'fcp';
    }

    protected function tearDown():void
    {
        Storage::disk('company')->deleteDirectory($this->company->id);
        parent::tearDown();
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @test
     */
    public function clone_check_base_data()
    {
        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];

        $response->assertStatus(201);
        $this->assertNotEmpty($cloned_project_id);
        $this->assertNotEquals($this->project->id, $cloned_project_id);

        $this->assertCount(2, Project::all());
        $this->assertNotEquals($cloned_project_id, $this->project->id);
        $this->assertNotEmpty($cloned_project_id);
        $this->assertNotEmpty($this->project->id);

        // check base project
        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
        ]);

        // check cloned project
        $this->assertDatabaseHas('projects', [
            'id' => $cloned_project_id,
            'company_id' => $this->project->company_id,
            'name' => $this->test_name,
            'short_name' => $this->test_short_name,
            'created_tickets' => (int) $this->project->created_tickets,
            'time_tracking_visible_for_clients' => (bool) $this->project->time_tracking_visible_for_clients,
            'language' => $this->project->language,
            'email_notification_enabled' => $this->project->email_notification_enabled,
            'slack_notification_enabled' => $this->project->slack_notification_enabled,
            'slack_webhook_url' => $this->project->slack_webhook_url,
            'slack_channel' => $this->project->slack_channel,
            'color' => $this->project->color,
            'closed_at' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
            'deleted_at' => null,
        ]);

        $cloned_project = Project::find($cloned_project_id);
        $this->assertNotEquals($this->project->created_at, $cloned_project->created_at);
        $this->assertNotEquals($this->project->updated_at, $cloned_project->updated_at);
        $this->assertNotEmpty($cloned_project->status_for_calendar_id);
        $this->assertNotEquals($this->project->status_for_calendar_id, $cloned_project->status_for_calendar_id);
        $this->assertSame(1, DB::transactionLevel());
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @depends clone_check_base_data
     * @test
     */
    public function clone_check_cloned_users_relation()
    {
        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];

        $cloned_project = Project::find($cloned_project_id);
        $this->assertGreaterThan(0, $this->project->users->count());
        $this->assertCount($this->project->users->count(), $cloned_project->users);

        foreach ($this->project->users as $user) {
            $cloned_user = $cloned_project->users()->where('user_id', $user->id)->first();
            $this->assertEquals($user->id, $cloned_user->id);
            $this->assertEquals($user->pivot->role_id, $cloned_user->pivot->role_id);
        }
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @depends clone_check_base_data
     * @test
     */
    public function clone_check_cloned_sprints_relation()
    {
        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];

        $cloned_project = Project::find($cloned_project_id);
        $this->assertGreaterThan(0, $this->project->sprints->count());
        $this->assertCount($this->project->sprints->count(), $cloned_project->sprints);

        foreach ($this->project->sprints as $key => $sprint) {
            $this->assertNotFalse($cloned_project->sprints->search(function ($item) use ($sprint) {
                return $item->name == $sprint->name
                    && $item->status == $sprint->status
                    && $item->priority == $sprint->priority
                    && $item->planned_activation == $sprint->planned_activation
                    && $item->planned_closing == $sprint->planned_closing
                    && $item->closed_at == $sprint->closed_at
                    && $item->activated_at == $sprint->activated_at;
            }));
        }
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @depends clone_check_base_data
     * @test
     */
    public function clone_check_cloned_tickets_relation()
    {
        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];

        $cloned_project = Project::find($cloned_project_id);
        $this->assertGreaterThan(0, $this->project->tickets->count());
        $this->assertCount($this->project->tickets->count(), $cloned_project->tickets);

        // check tickets
        foreach ($this->project->tickets as $key => $ticket) {
            $title_array = explode('-', $ticket->title);
            $title_array[0] = mb_strtoupper($this->test_short_name);
            $expected_title = implode('-', $title_array);

            $this->assertNotFalse($cloned_project->sprints->search(function ($item) use ($ticket) {
                return $item->id = $ticket->sprint_id;
            }));

            $key = $cloned_project->tickets->search(function ($item) use ($ticket, $expected_title) {
                return $item->name == $ticket->name
                    && $item->title == $expected_title
                    && $item->type_id == $ticket->type_id
                    && $item->assigned_id == $ticket->assigned_id
                    && $item->reporter_id == $ticket->reporter_id
                    && $item->description == $ticket->description
                    && $item->estimate_time == $ticket->estimate_time
                    && $item->scheduled_time_start == $ticket->scheduled_time_start
                    && $item->scheduled_time_end == $ticket->scheduled_time_end
                    && $item->priority == $ticket->priority
                    && $item->hidden == $ticket->hidden;
            });
            $this->assertNotFalse($key);
            $this->assertCount($ticket->comments->count(), $cloned_project->tickets[$key]->comments);
            $this->assertCount($ticket->stories->count(), $cloned_project->tickets[$key]->stories);
            $this->assertNotEquals($ticket->sprint->id, $cloned_project->tickets[$key]->sprint->id);
        }
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @depends clone_check_base_data
     * @test
     */
    public function clone_check_cloned_related_tickets()
    {
        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];

        $cloned_project = Project::find($cloned_project_id);
        $this->assertGreaterThan(0, $this->project->tickets->count());
        $this->assertCount($this->project->tickets->count(), $cloned_project->tickets);

        $this->assertEquals(1, $this->project->tickets[0]->subTickets()->count());
        $this->assertEquals(1, $cloned_project->tickets[0]->subTickets()->count());

        $this->assertEquals(
            $cloned_project->tickets[0]->subTickets()->first()->id,
            $cloned_project->tickets[1]->id
        );
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @depends clone_check_base_data
     * @test
     */
    public function clone_check_cloned_stories_relation()
    {
        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];

        $cloned_project = Project::find($cloned_project_id);
        $this->assertGreaterThan(0, $this->project->stories->count());
        $this->assertCount($this->project->stories->count(), $cloned_project->stories);

        foreach ($this->project->stories as $key => $story) {
            $this->assertNotFalse($cloned_project->stories->search(function ($item) use ($story) {
                return $item->name == $story->name
                    && $item->color == $story->color
                    && $item->priority == $story->priority;
            }));
        }
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @depends clone_check_base_data
     * @test
     */
    public function clone_check_cloned_statuses_relation()
    {
        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];

        $cloned_project = Project::find($cloned_project_id);
        $this->assertGreaterThan(0, $this->project->statuses->count());
        $this->assertCount($this->project->statuses->count(), $cloned_project->statuses);

        foreach ($this->project->statuses as $key => $status) {
            $this->assertNotFalse($cloned_project->statuses->search(function ($item) use ($status) {
                return $item->name == $status->name
                    && $item->priority == $status->priority;
            }));
        }
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @depends clone_check_base_data
     * @test
     */
    public function clone_check_cloned_files_relation()
    {
        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];

        $cloned_project = Project::find($cloned_project_id);
        $this->assertCount($this->project->files->count(), $cloned_project->files);
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @depends clone_check_base_data
     * @test
     */
    public function clone_check_cloned_permissions()
    {
        $fields_to_compare = ['ticket_create', 'ticket_update', 'ticket_destroy',
            'ticket_comment_create', 'ticket_comment_update', 'ticket_comment_destroy',
            'owner_ticket_show', 'admin_ticket_show', 'developer_ticket_show', 'client_ticket_show', ];

        $ticket_create_permissions = $this->project->permission->ticket_create;
        $ticket_create_permissions['roles'][0]['value'] = false;
        $this->project->permission->ticket_create = $ticket_create_permissions;
        $this->project->permission->save();

        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];

        $cloned_project = Project::find($cloned_project_id);
        $this->assertEquals($cloned_project->id, $cloned_project->permission->project_id);
        $this->assertCount(2, ProjectPermission::all());
        foreach ($fields_to_compare as $field) {
            $this->assertEquals(
                $this->project->permission->{$field},
                $cloned_project->permission->{$field}
            );
        }
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @depends clone_check_base_data
     * @test
     */
    public function clone_check_attached_tickets_to_cloned_files()
    {
        $this->createFile('file.jpg');
        $ticket = $this->project->tickets->first();
        $file = $this->project->files->first();
        $file->tickets()->attach($ticket);

        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];
        $cloned_project = Project::find($cloned_project_id);

        $cloned_file_key = $cloned_project->files->search(function ($item) use ($file) {
            return $item->name === $file->name;
        });

        $cloned_file = $cloned_project->files[$cloned_file_key];

        $this->assertGreaterThan(0, $file->tickets->count());
        $this->assertCount($file->tickets->count(), $cloned_file->tickets);
        $this->assertEquals($cloned_project->id, $cloned_file->project_id);

        foreach ($file->tickets as $key => $ticket) {
            $this->assertNotEquals($ticket->id, $cloned_file->tickets[$key]->id);
            $this->assertEquals($ticket->name, $cloned_file->tickets[$key]->name);
            $this->assertEquals($cloned_project->id, $cloned_file->tickets[$key]->project_id);
        }
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     *
     * @test
     */
    public function clone_check_attached_stories_to_cloned_files()
    {
        $this->createFile('file.jpg');
        $story = $this->project->stories->first();
        $file = $this->project->files->first();
        $story->files()->attach($file);

        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];
        $cloned_project = Project::find($cloned_project_id);
        $cloned_story = $cloned_project->stories->first();

        foreach ($story->files as $key => $file) {
            $this->assertNotEquals($story->id, $cloned_story->id);
            $this->assertEquals($story->name, $cloned_story->name);
            $this->assertEquals($file->name, $cloned_story->files[$key]->name);
            $this->assertEquals($cloned_project->id, $cloned_story->files[$key]->project_id);
        }
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @test
     */
    public function clone_gets_error_when_package_limit_reached()
    {
        // check if there are no projects with this name
        $this->assertEmpty($this->user->fresh()->projects->where('name', $this->test_name));

        factory(Project::class, 5)->create(['company_id' => $this->company->id]);

        $user = factory(User::class)->create();
        $user->companies()->attach($this->company->id);

        $projectsCount = Project::all()->count();

        $response = $this->cloneProject();
        $response->assertStatus(409);

        $decoded_response = $response->decodeResponseJson();
        $this->assertEquals(ErrorCode::PACKAGE_LIMIT_REACHED, $decoded_response['code']);
        $this->assertCount($projectsCount, Project::all());
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @test
     */
    public function clone_try_clone_project_with_client_role_and_get_401_exception()
    {
        $this->company = $this->createCompanyWithRole(RoleType::CLIENT);

        $projectsCount = Project::all()->count();

        $response = $this->cloneProject();
        $response->assertStatus(401);

        $decoded_response = $response->decodeResponseJson();
        $this->assertEquals(ErrorCode::NO_PERMISSION, $decoded_response['code']);
        $this->assertCount($projectsCount, Project::all());
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @test
     */
    public function clone_url_with_no_existing_company_id_should_throw_401_exception()
    {
        $company_id = (int) $this->company->id + 1;

        $response = $this->post("/projects/{$this->project->id}/clone?selected_company_id="
            . $company_id, [
            'name' => $this->test_name,
            'short_name' => $this->test_short_name,
        ]);

        $response->assertStatus(401);
        $decoded_response = $response->decodeResponseJson();
        $this->assertEquals(ErrorCode::NO_PERMISSION, $decoded_response['code']);
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @test
     */
    public function clone_url_with_wrong_company_id_should_throw_401_exception()
    {
        $otherCompany = factory(Company::class)->create();

        $response = $this->post("/projects/{$this->project->id}/clone?selected_company_id="
            . $otherCompany->id, [
            'name' => $this->test_name,
            'short_name' => $this->test_short_name,
        ]);

        $response->assertStatus(401);
        $decoded_response = $response->decodeResponseJson();
        $this->assertEquals(ErrorCode::NO_PERMISSION, $decoded_response['code']);
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @test
     */
    public function clone_send_empty_data_should_throw_validation_error()
    {
        $response = $this->post($this->getUrl(), [
            'name' => '',
            'short_name' => '',
        ]);

        $response->assertStatus(422);
        $decoded_response = $response->decodeResponseJson();
        $this->assertEquals(ErrorCode::VALIDATION_FAILED, $decoded_response['code']);
    }

    /**
     * @covers \App\Modules\Project\Http\Controllers\ProjectController::clone
     * @test
     */
    public function clone_and_get_cloned_project_and_related_objects()
    {
        $response = $this->cloneProject();
        $cloned_project_id = $response->decodeResponseJson()['data']['id'];

        $this->get("/projects/{$cloned_project_id}/?selected_company_id={$this->company->id}")
            ->assertStatus(200);

        $this->get("/projects/{$cloned_project_id}/sprints"
            . "?selected_company_id={$this->company->id}&status=not-closed")
            ->assertStatus(200);

        $this->get("/projects/{$cloned_project_id}/statuses"
            . "?selected_company_id={$this->company->id}&tickets=1")
            ->assertStatus(200);
    }

    /**
     * @param string $time
     */
    private function mockTime(string $time): void
    {
        $this->now = Carbon::parse($time);
        Carbon::setTestNow($this->now);
    }

    /**
     * @return string
     */
    private function getUrl()
    {
        $params = ['project' => $this->project->id,
            'selected_company_id' => $this->company->id, ];

        return route('projects.clone', $params);
    }

    /**
     * @return TestResponse
     */
    private function cloneProject()
    {
        $this->mockTime('2019-07-08 12:12:12');

        $name = $this->test_name;
        $short_name = $this->test_short_name;
        $payload = compact('name', 'short_name');
        $url = $this->getUrl();

        return $this->post($url, $payload);
    }
}
