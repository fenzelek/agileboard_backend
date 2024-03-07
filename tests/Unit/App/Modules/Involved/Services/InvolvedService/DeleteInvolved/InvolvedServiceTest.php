<?php
declare(strict_types=1);

namespace Tests\Unit\App\Modules\Involved\Services\InvolvedService\DeleteInvolved;

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
     * @scenario Delete document page's involved list
     * @case Involved list contains three position with one related with other document page
     * @expectation Left only involved related with other document page
     * @test
     */
    public function deleteInvolved_involvedListContainsThreePositionWithOneRelatedWithOtherDocumentPage()
    {
        //GIVEN
        Event::fake();

        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $company = $this->createCompany();
        $project = $this->createProject();

        $knowledge_page = $this->createKnowledgePage($project->id);;
        $knowledge_page_other = $this->createKnowledgePage($project->id);;

        $this->createInvolved([
            'user_id' => $user_1->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $knowledge_page_other->id,
            'source_type' => MorphMap::KNOWLEDGE_PAGES,
        ]);

        $this->createInvolved([
            'user_id' => $user_1->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $knowledge_page->id,
            'source_type' => MorphMap::KNOWLEDGE_PAGES,
        ]);

        $this->createInvolved([
            'user_id' => $user_2->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $knowledge_page->id,
            'source_type' => MorphMap::KNOWLEDGE_PAGES,
        ]);

        //WHEN
        $this->involved_service->deleteInvolved($knowledge_page);

        //THEN
        $this->assertCount(0, $knowledge_page->involved()->get());

        $this->assertDatabaseCount(Involved::class, 1);
        $this->assertDatabaseHas(Involved::class, [
            'user_id' =>  $user_1->id,
            'source_type' => MorphMap::KNOWLEDGE_PAGES,
            'source_id' => $knowledge_page_other->id,
            'project_id' => $project->id,
            'company_id' => $company->getCompanyId()
        ]);
    }

    /**
     * @feature Involved
     * @scenario Delete ticket's involved list
     * @case Involved list contains three position with one related with other ticket
     * @expectation Left only involved related with other ticket
     * @test
     */
    public function deleteInvolved_involvedListContainsThreePositionWithOneRelatedWithOtherTicket()
    {
        //GIVEN
        Event::fake();

        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $company = $this->createCompany();
        $project = $this->createProject();

        $ticket = $this->createTicket($project->id);
        $ticket_other = $this->createTicket($project->id);

        $this->createInvolved([
            'user_id' => $user_1->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $ticket_other->id,
            'source_type' => MorphMap::TICKETS,
        ]);

        $this->createInvolved([
            'user_id' => $user_1->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $ticket->id,
            'source_type' => MorphMap::TICKETS,
        ]);

        $this->createInvolved([
            'user_id' => $user_2->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $ticket->id,
            'source_type' => MorphMap::TICKETS,
        ]);

        //WHEN
        $this->involved_service->deleteInvolved($ticket);

        //THEN
        $this->assertCount(0, $ticket->involved()->get());

        $this->assertDatabaseCount(Involved::class, 1);
        $this->assertDatabaseHas(Involved::class, [
            'user_id' =>  $user_1->id,
            'source_type' => MorphMap::TICKETS,
            'source_id' => $ticket_other->id,
            'project_id' => $project->id,
            'company_id' => $company->getCompanyId()
        ]);
    }
}