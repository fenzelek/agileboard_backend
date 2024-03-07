<?php

namespace Tests\Unit\App\Modules\Agile\Services\DashboardService;

use App\Models\Db\Company;
use App\Models\Db\Sprint;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Agile\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;
use Tests\Helpers\CreateProjects;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use DatabaseTransactions;
    use DashboardServiceTrait;
    use CreateProjects;

    /**
     * @var DashboardService
     */
    private $service;
    protected $company;
    protected $new_company;
    protected $now;
    protected $developer;

    public function setUp(): void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $this->new_company = factory(Company::class)->create();

        $this->developer = factory(User::class)->create();

        $this->service = $this->app->make(DashboardService::class);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case Project in user company
     * @test
     */
    public function getWidgets_projectInUserCompany()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $this->createProject($this->company->id, $this->user);
        $this->createProject($this->company->id, $this->user);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $this->assertEquals('YourProjects', $result[array_key_first($result)]->getName());
        $data = $result[array_key_first($result)]->getData();
        $this->assertCount(2, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case Projects in other company
     * @test
     */
    public function getWidgets_projectInOtherCompany()
    {
        // GIVEN
        $this->createProject($this->company->id, $this->user);
        $this->createProject($this->company->id, $this->user);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $this->assertEquals('YourProjects', $result[array_key_first($result)]->getName());

        $data = $result[array_key_first($result)]->getData();
        $this->assertCount(0, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case Projects are closed
     * @test
     */
    public function getWidgets_projectIsClosed()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $this->createProject($this->company->id, $this->user, Carbon::now());

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $this->assertEquals('YourProjects', $result[array_key_first($result)]->getName());

        $data = $result[array_key_first($result)]->getData();
        $this->assertCount(0, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case User not participed in projects
     * @test
     */
    public function getWidgets_userNotParticipedInProject()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $this->createProject($this->company->id, $this->user);
        $this->createProject($this->company->id, $this->user);

        $this->userEmail = 'useremail_second@example.com';
        $this->userPassword = 'testpassword';

        $user = $this->createNewUser();

        //WHEN
        $result = $this->service->getWidgets($user);

        //THEN
        $this->assertCount(3, $result);
        $this->assertEquals('YourProjects', $result[array_key_first($result)]->getName());

        $data = $result[array_key_first($result)]->getData();
        $this->assertCount(0, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case Your task belongs to user company
     * @test
     */
    public function getWidgets_yourTasksBelongsToUserCompany()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $project = $this->createProject($this->company->id, $this->user);

        $sprint = $this->createSprint($project->id, 'sprint_1');

        auth()->loginUsingId($this->user->id);

        $status_backlog = $this->createStatus($project->id, 'backlog', 0);
        $this->createStatus($project->id, 'done', 1);

        $this->createTicket($project->id, $sprint->id, $this->user->id, $status_backlog->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id, $status_backlog->id);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\YourTasks::class];

        $this->assertEquals('YourTasks', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(2, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case YourTask some tasks are done
     * @test
     */
    public function getWidgets_yourTaskSomeTasksAreDone()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $project = $this->createProject($this->company->id, $this->user);

        $sprint = $this->createSprint($project->id, 'sprint_1');

        auth()->loginUsingId($this->user->id);

        $status_backlog = $this->createStatus($project->id, 'backlog', 0);
        $status_done = $this->createStatus($project->id, 'done', 1);


        $this->createTicket($project->id, $sprint->id, $this->user->id, $status_backlog->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id, $status_backlog->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id, $status_done->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id, $status_done->id);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\YourTasks::class];

        $this->assertEquals('YourTasks', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(2, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case YourTasks two projects with only done tasks
     * @test
     */
    public function getWidgets_yourTasksTwoProjectsWithOnlyDoneTasks()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $project_1 = $this->createProject($this->company->id, $this->user);
        $project_2 = $this->createProject($this->company->id, $this->user);

        $sprint_1 = $this->createSprint($project_1->id, 'sprint_1');
        $sprint_2 = $this->createSprint($project_2->id, 'sprint_2');

        auth()->loginUsingId($this->user->id);

        $status_backlog_1 = $this->createStatus($project_1->id, 'backlog', 0);
        $status_done_1 = $this->createStatus($project_1->id, 'done', 1);

        $status_backlog_2 = $this->createStatus($project_2->id, 'backlog', 0);
        $status_done_2 = $this->createStatus($project_2->id, 'done', 3);

        $this->createTicket($project_1->id, $sprint_1->id, $this->user->id, $status_done_1->id);
        $this->createTicket($project_1->id, $sprint_1->id, $this->user->id, $status_done_1->id);

        $this->createTicket($project_2->id, $sprint_2->id, $this->user->id, $status_done_2->id);
        $this->createTicket($project_2->id, $sprint_2->id, $this->user->id, $status_done_2->id);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\YourTasks::class];

        $this->assertEquals('YourTasks', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(0, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case YourTasks two projects with mixed status task
     * @test
     */
    public function getWidgets_YourTasksTwoProjectsWithMixedStatusTask()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $project_1 = $this->createProject($this->company->id, $this->user);
        $project_2 = $this->createProject($this->company->id, $this->user);

        $sprint_1 = $this->createSprint($project_1->id, 'sprint_1');
        $sprint_2 = $this->createSprint($project_2->id, 'sprint_2');

        auth()->loginUsingId($this->user->id);

        $status_backlog_1 = $this->createStatus($project_1->id, 'backlog', 0);
        $status_done_1 = $this->createStatus($project_1->id, 'done', 1);

        $status_backlog_2 = $this->createStatus($project_2->id, 'backlog', 0);
        $status_in_progress_2 = $this->createStatus($project_2->id, 'backlog', 1);
        $status_done_2 = $this->createStatus($project_2->id, 'done', 2);

        $this->createTicket($project_1->id, $sprint_1->id, $this->user->id, $status_backlog_1->id);
        $this->createTicket($project_1->id, $sprint_1->id, $this->user->id, $status_done_1->id);

        $this->createTicket($project_2->id, $sprint_2->id, $this->user->id, $status_backlog_2->id);
        $this->createTicket($project_2->id, $sprint_2->id, $this->user->id, $status_in_progress_2->id);
        $this->createTicket($project_2->id, $sprint_2->id, $this->user->id, $status_done_2->id);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\YourTasks::class];

        $this->assertEquals('YourTasks', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(3, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case YourTasks tasks belongs to other company
     * @test
     */
    public function getWidgets_yourTasksTasksBelongsToOtherCompany()
    {
        // GIVEN
        $project = $this->createProject($this->company->id, $this->user);

        $sprint = $this->createSprint($project->id, 'sprint_1');

        auth()->loginUsingId($this->user->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\YourTasks::class];

        $this->assertEquals('YourTasks', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(0, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case YourTasks tasks assign to other user
     * @test
     */
    public function getWidgets_YourTasksTasksBelongsToOtherCompany_()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $project = $this->createProject($this->company->id, $this->user);

        $sprint = $this->createSprint($project->id, 'sprint_1');

        $this->createNewUser();
        auth()->loginUsingId($this->user->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\YourTasks::class];

        $this->assertEquals('YourTasks', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(0, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case YourTasks tasks belongs to inactive sprint
     * @test
     */
    public function getWidgets_yourTasksTasksBelongsToInactiveSprint()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $project = $this->createProject($this->company->id, $this->user);

        $sprint = $this->createSprint($project->id, 'sprint_1', Sprint::INACTIVE);

        auth()->loginUsingId($this->user->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\YourTasks::class];

        $this->assertEquals('YourTasks', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(0, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case LastAdded belongs to user company
     * @test
     */
    public function getWidgets_LastAddedBelongsToUserCompany()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $project = $this->createProject($this->company->id, $this->user);

        $sprint = $this->createSprint($project->id, 'sprint_1');

        auth()->loginUsingId($this->user->id);
        $ticket = $this->createTicket($project->id, $sprint->id, $this->user->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id);

        $story = $this->createStory($project->id, 'test_story');
        $ticket->stories()->attach($story);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\LastAddedList::class];

        $this->assertEquals('LastAdded', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(2, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case LastAdded task belongs to other company
     * @test
     */
    public function getWidgets_lastAddedTaskBelongsToOtherCompany()
    {
        // GIVEN
        $project = $this->createProject($this->company->id, $this->user);

        $sprint = $this->createSprint($project->id, 'sprint_1');

        auth()->loginUsingId($this->user->id);
        $ticket = $this->createTicket($project->id, $sprint->id, $this->user->id);
        $this->createTicket($project->id, $sprint->id, $this->user->id);

        $story = $this->createStory($project->id, 'test_story');
        $ticket->stories()->attach($story);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\LastAddedList::class];

        $this->assertEquals('LastAdded', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(0, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case LastAdded belongs to other user
     * @test
     */
    public function getWidgets_LastAddedBelongsToOtherUser()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $project = $this->createProject($this->company->id, $this->user);

        $sprint = $this->createSprint($project->id, 'sprint_1');

        $user = $this->createNewUser();
        auth()->loginUsingId($user->id);
        $ticket = $this->createTicket($project->id, $sprint->id, $user->id);
        $this->createTicket($project->id, $sprint->id, $user->id);

        $story = $this->createStory($project->id, 'test_story');
        $ticket->stories()->attach($story);

        //WHEN
        $result = $this->service->getWidgets($user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\LastAddedList::class];

        $this->assertEquals('LastAdded', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(0, $data);
    }

    /**
     * @feature Dashboard
     * @scenario Get Widgets
     * @case LastAdded older than three months
     * @test
     */
    public function getWidgets_lastAddedOlderThanThreeMonths()
    {
        // GIVEN
        $this->user->setSelectedCompany($this->company->id);
        $project = $this->createProject($this->company->id, $this->user);

        $sprint = $this->createSprint($project->id, 'sprint_1');

        auth()->loginUsingId($this->user->id);
        $ticket = $this->createTicket($project->id, $sprint->id, $this->user->id);
        $ticket->created_at = Carbon::now()->subMonths(4);
        $ticket->update();
        $this->createTicket($project->id, $sprint->id, $this->user->id);

        $story = $this->createStory($project->id, 'test_story');
        $ticket->stories()->attach($story);

        //WHEN
        $result = $this->service->getWidgets($this->user);

        //THEN
        $this->assertCount(3, $result);
        $tasks = $result[\App\Http\Resources\LastAddedList::class];

        $this->assertEquals('LastAdded', $tasks->getName());

        $data = $tasks->getData();
        $this->assertCount(1, $data);
    }
}
