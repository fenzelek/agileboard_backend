<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\SprintController;

use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\CreateSprintEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class StoreTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /** @test */
    public function store_it_returns_validation_error_without_data()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $this->post(
            '/projects/' . $project->id . '/sprints?selected_company_id=' . $company->id,
            []
        );

        $this->verifyValidationResponse(['name']);
    }

    /** @test */
    public function store_it_returns_validation_wrong_times()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $data = [
            'name' => ' test',
            'planned_activation' => '2017-11-20 10:10:10',
            'planned_closing' => '2017-10-20 10:10:10',
        ];

        $this->post('/projects/' . $project->id . '/sprints?selected_company_id=' . $company->id, $data);

        $this->verifyValidationResponse(['planned_activation', 'planned_closing']);
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
            '/projects/' . $project->id . '/sprints?selected_company_id=' . $company->id,
            ['name' => ' test']
        )
            ->seeStatusCode(201);

        Event::assertDispatched(CreateSprintEvent::class, function ($e) use ($project) {
            if (
                $e->project->id == $project->id &&
                $e->sprint->name == 'test') {
                return true;
            }
        });

        $response_sprint = $this->decodeResponseJson()['data'];

        $this->assertSame('test', $response_sprint['name']);
        $this->assertSame($project->id, $response_sprint['project_id']);
        $this->assertSame(Sprint::INACTIVE, $response_sprint['status']);
        $this->assertSame(1, $response_sprint['priority']);
        $this->assertSame($now->toDateTimeString(), $response_sprint['created_at']);
        $this->assertSame($now->toDateTimeString(), $response_sprint['updated_at']);
        $this->assertSame(null, $response_sprint['planned_activation']);
        $this->assertSame(null, $response_sprint['planned_closing']);
        $this->assertSame(null, $response_sprint['activated_at']);
        $this->assertSame(null, $response_sprint['closed_at']);
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

        $before_sprints = Sprint::count();

        $data = [
            'name' => ' test',
            'planned_activation' => '2017-10-20 10:10:10',
            'planned_closing' => '2017-11-20 10:10:10',
        ];

        $this->post(
            '/projects/' . $project->id . '/sprints?selected_company_id=' . $company->id,
            $data
        )
            ->seeStatusCode(201);

        $this->assertEquals($before_sprints + 1, Sprint::count());
        $sprint = Sprint::latest('id')->first();

        $this->assertSame('test', $sprint->name);
        $this->assertSame($project->id, $sprint->project_id);
        $this->assertSame(Sprint::INACTIVE, $sprint->status);
        $this->assertSame(1, $sprint->priority);
        $this->assertSame($now->toDateTimeString(), $sprint->created_at->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $sprint->updated_at->toDateTimeString());
        $this->assertSame('2017-10-20 10:10:10', $sprint->planned_activation->toDateTimeString());
        $this->assertSame('2017-11-20 10:10:10', $sprint->planned_closing->toDateTimeString());
    }

    /** @test */
    public function store_success_response_next()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        factory(Sprint::class)->create(['project_id' => $project->id, 'priority' => 1]);

        $this->post(
            '/projects/' . $project->id . '/sprints?selected_company_id=' . $company->id,
            ['name' => 'test']
        )
            ->seeStatusCode(201);

        $response_sprint = $this->decodeResponseJson()['data'];

        $this->assertSame('test', $response_sprint['name']);
        $this->assertSame($project->id, $response_sprint['project_id']);
        $this->assertSame(Sprint::INACTIVE, $response_sprint['status']);
        $this->assertSame(2, $response_sprint['priority']);
        $this->assertSame($now->toDateTimeString(), $response_sprint['created_at']);
        $this->assertSame($now->toDateTimeString(), $response_sprint['updated_at']);
    }

    public function store_success_db_next()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        factory(Sprint::class)->create(['project_id' => $project->id, 'priority' => 1]);

        $before_sprints = Sprint::count();

        $this->post(
            '/projects/' . $project->id . '/sprints?selected_company_id=' . $company->id,
            ['name' => ' test']
        )
            ->seeStatusCode(201);

        $this->assertEquals($before_sprints + 1, Sprint::count());
        $sprint = Sprint::latest('id')->first();

        $this->assertSame('test', $sprint->name);
        $this->assertSame($project->id, $sprint->project_id);
        $this->assertSame(Sprint::INACTIVE, $sprint->status);
        $this->assertSame(2, $sprint->priority);
        $this->assertSame($now->toDateTimeString(), $sprint->created_at->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $sprint->updated_at->toDateTimeString());
    }

    /** @test */
    public function store_success_response_only_planned_activation()
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
            '/projects/' . $project->id . '/sprints?selected_company_id=' . $company->id,
            [
                'name' => ' test',
                'planned_activation' => '2017-10-20 10:10:10',
            ]
        )
            ->seeStatusCode(201);

        $response_sprint = $this->decodeResponseJson()['data'];

        $this->assertSame('test', $response_sprint['name']);
    }

    /** @test */
    public function store_success_response_only_planned_closing()
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
            '/projects/' . $project->id . '/sprints?selected_company_id=' . $company->id,
            [
                'name' => ' test',
                'planned_closing' => '2017-10-20 10:10:10',
            ]
        )
            ->seeStatusCode(201);

        $response_sprint = $this->decodeResponseJson()['data'];

        $this->assertSame('test', $response_sprint['name']);
    }
}
