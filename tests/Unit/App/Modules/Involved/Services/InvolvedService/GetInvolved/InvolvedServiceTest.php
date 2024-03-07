<?php
declare(strict_types=1);

namespace Tests\Unit\App\Modules\Involved\Services\InvolvedService\GetInvolved;

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
     * @scenario Show document page's involved list
     * @case Involved list contains two position
     * @expectation Return list with valid users
     * @test
     */
    public function getInvolved_involvedListContainsTwoPosition()
    {
        //GIVEN
        Event::fake();

        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $company = $this->createCompany();
        $project = $this->createProject();

        $knowledge_page = $this->createKnowledgePage($project->id);;

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
        $result = $this->involved_service->getInvolvedUsers($knowledge_page);

        //THEN
        $this->assertCount(2, $result);

        /** @var Involved $involved_1 */
        $involved_1 = $result->first();
        $this->assertEquals($user_1->id, $involved_1->user_id);

        /** @var Involved $involved_2 */
        $involved_2 = $result->last();
        $this->assertEquals($user_2->id, $involved_2->user_id);
    }

    /**
     * @feature Involved
     * @scenario Show ticket's involved list
     * @case Involved list contains three position where one is related with other company
     * @expectation Return two position list with valid users
     * @test
     */
    public function getInvolved_involvedListContainsThreePositionWhereOneIsRelatedWithOtherCompany()
    {
        //GIVEN
        Event::fake();

        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $company = $this->createCompany();
        $company_other = $this->createCompany();

        $project = $this->createProject();

        $ticket = $this->createTicket($project->id);
        $ticket_other = $this->createTicket($project->id);

        $this->createInvolved([
            'user_id' => $user_1->id,
            'company_id' => $company_other->getCompanyId(),
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
        $result = $this->involved_service->getInvolvedUsers($ticket);

        //THEN
        $this->assertCount(2, $result);

        /** @var Involved $involved_1 */
        $involved_1 = $result->first();
        $this->assertEquals($user_1->id, $involved_1->user_id);

        /** @var Involved $involved_2 */
        $involved_2 = $result->last();
        $this->assertEquals($user_2->id, $involved_2->user_id);
    }


    /**
     * @feature Involved
     * @scenario Show ticket's involved list
     * @case Involved list contains three position where one is related with other project
     * @expectation Return two position list with valid users
     * @test
     */
    public function getInvolved_involvedListContainsThreePositionWhereOneIsRelatedWithOtherProject()
    {
        //GIVEN
        Event::fake();

        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $company = $this->createCompany();

        $project = $this->createProject();
        $project_other = $this->createProject();

        $ticket = $this->createTicket($project->id);
        $ticket_other = $this->createTicket($project->id);

        $this->createInvolved([
            'user_id' => $user_1->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project_other->id,
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
        $result = $this->involved_service->getInvolvedUsers($ticket);

        //THEN
        $this->assertCount(2, $result);

        /** @var Involved $involved_1 */
        $involved_1 = $result->first();
        $this->assertEquals($user_1->id, $involved_1->user_id);

        /** @var Involved $involved_2 */
        $involved_2 = $result->last();
        $this->assertEquals($user_2->id, $involved_2->user_id);
    }
}