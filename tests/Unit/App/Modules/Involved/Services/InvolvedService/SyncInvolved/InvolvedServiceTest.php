<?php
declare(strict_types=1);

namespace Tests\Unit\App\Modules\Involved\Services\InvolvedService\SyncInvolved;


use App\Models\Db\Involved;
use App\Models\Other\MorphMap;
use App\Modules\Involved\Services\InvolvedService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InvolvedServiceTest extends TestCase
{
    use DatabaseTransactions;
    use InvolvedServiceTrait;

    /**
     * @var InvolvedService
     */
    private $involved_service;

    public function setUp():void
    {
        parent::setUp();

        $this->involved_service = $this->app->make(InvolvedService::class);
    }

    /**
     * @feature Involved
     * @scenario Store/Update involved with ticket
     * @case New involved list contains two user
     * @expectation Involved list contains only new users
     * @test
     */
    public function syncInvolved_newInvolvedListContainsTwoUser()
    {
        //GIVEN
        Event::fake();

        $user_current = $this->createNewUser();

        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $company = $this->createCompany();
        $project = $this->createProject();

        $ticket = $this->createTicket($project->id);

        $this->createInvolved([
            'user_id' => $user_current->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $ticket->id,
            'source_type' => MorphMap::TICKETS,
        ]);

        $involved_request = $this->getInvolvedRequestMock(
            $company->getCompanyId(),
            $project->id,
            [
                $user_1->id,
                $user_2->id,
            ]
        );

        //WHEN
        $this->involved_service->syncInvolved($involved_request, $ticket);

        //THEN
        $this->assertDatabaseCount(Involved::class, 2);
        $this->assertDatabaseHas(Involved::class, [
           'user_id' =>  $user_1->id,
           'source_type' => MorphMap::TICKETS,
           'source_id' => $ticket->id,
           'project_id' => $project->id,
           'company_id' => $company->getCompanyId()
        ]);

        $this->assertDatabaseHas(Involved::class, [
           'user_id' =>  $user_2->id,
           'source_type' => MorphMap::TICKETS,
           'source_id' => $ticket->id,
           'project_id' => $project->id,
           'company_id' => $company->getCompanyId()
        ]);
    }

    /**
     * @feature Involved
     * @scenario Store/Update involved with ticket
     * @case New involved list is empty
     * @expectation Involved list is empty
     * @test
     */
    public function syncInvolved_newInvolvedListIsEmpty()
    {
        //GIVEN
        Event::fake();

        $user_current = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject();

        $ticket = $this->createTicket($project->id);

        $this->createInvolved([
            'user_id' => $user_current->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $ticket->id,
            'source_type' => MorphMap::TICKETS,
        ]);

        $involved_request = $this->getInvolvedRequestMock(
            $company->getCompanyId(),
            $project->id,
            []
        );

        //WHEN
        $this->involved_service->syncInvolved($involved_request, $ticket);

        //THEN
        $this->assertDatabaseCount(Involved::class, 0);
    }

    /**
     * @feature Involved
     * @scenario Store/Update involved with document page
     * @case New involved list contains two position
     * @expectation Involved list contains only new positions
     * @test
     */
    public function syncInvolved_newInvolvedListContainsTwoPosition()
    {
        //GIVEN
        Event::fake();

        $user_current = $this->createNewUser();
        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $company = $this->createCompany();
        $project = $this->createProject();

        $knowledge_page = $this->createKnowledgePage($project->id);

        $this->createInvolved([
            'user_id' => $user_current->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $knowledge_page->id,
            'source_type' => MorphMap::KNOWLEDGE_PAGES,
        ]);

        $involved_request = $this->getInvolvedRequestMock(
            $company->getCompanyId(),
            $project->id,
            [
                $user_1->id,
                $user_2->id,
            ]
        );

        //WHEN
        $this->involved_service->syncInvolved($involved_request, $knowledge_page);

        //THEN
        $this->assertDatabaseCount(Involved::class, 2);
        $this->assertDatabaseHas(Involved::class, [
            'user_id' =>  $user_1->id,
            'source_type' => MorphMap::KNOWLEDGE_PAGES,
            'source_id' => $knowledge_page->id,
            'project_id' => $project->id,
            'company_id' => $company->getCompanyId()
        ]);

        $this->assertDatabaseHas(Involved::class, [
            'user_id' =>  $user_2->id,
            'source_type' => MorphMap::KNOWLEDGE_PAGES,
            'source_id' => $knowledge_page->id,
            'project_id' => $project->id,
            'company_id' => $company->getCompanyId()
        ]);
    }
}