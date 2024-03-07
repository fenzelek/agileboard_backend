<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\DashboardController;

use App\Models\Db\TicketType;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;
use Tests\Helpers\CreateProjects;

class IndexTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    use CreateProjects;
    use IndexTrait;

    protected $company;
    protected $now;
    protected $developer;

    public function setUp(): void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        TicketType::query()->forceDelete();
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $this->developer = factory(User::class)->create();
    }

    public function tearDown(): void
    {
        TicketType::query()->forceDelete();
        parent::tearDown();
    }

    /** @test */
    public function index_return_widgets()
    {
        // GIVEN
        $project = $this->createProject($this->company->id, $this->user);

        $sprint = $this->createSprint($project->id, 'sprint_1');

        $status_backlog = $this->createStatus($project->id, 'backlog', 0);
        $this->createStatus($project->id, 'done', 1);

        $ticket = $this->createTicket($project->id, $sprint->id, $this->user->id, $status_backlog->id);

        $ticketType = $this->createTicketType(1, 'test_ticket_type');
        $ticket->type()->associate($ticketType);

        $story = $this->createStory($project->id, 'test_story');
        $ticket->stories()->attach($story);

        // WHEN
        $response = $this->get('/dashboard/?selected_company_id=' . $this->company->id);

        //THEN
        $this->assertResponseStatus(200);

        $this->seeJsonStructure($this->getExpectedJsonStructure());
    }
}
