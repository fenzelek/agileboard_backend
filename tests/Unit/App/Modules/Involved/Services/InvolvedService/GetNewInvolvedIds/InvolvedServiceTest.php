<?php
declare(strict_types=1);

namespace Tests\Unit\App\Modules\Involved\Services\InvolvedService\GetNewInvolvedIds;


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
     * @expectation Return two position, valid data list
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
        $result = $this->involved_service->getNewInvolvedIds($involved_request, $ticket);

        //THEN
        $this->assertCount(2, $result);
        $this->assertJsonStringEqualsJsonString(json_encode([$user_1->id, $user_2->id]), json_encode($result));
    }

    /**
     * @feature Involved
     * @scenario Store/Update involved with ticket
     * @case New involved list is empty
     * @expectation Return empty list
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
        $result = $this->involved_service->getNewInvolvedIds($involved_request, $ticket);

        //THEN
        $this->assertCount(0, $result);
    }

    /**
     * @feature Involved
     * @scenario Store/Update involved with document page
     * @case New involved list contains two position
     * @expectation Return two position, valid data list
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
        $result = $this->involved_service->getNewInvolvedIds($involved_request, $knowledge_page);

        //THEN
        $this->assertCount(2, $result);
        $this->assertJsonStringEqualsJsonString(json_encode([$user_1->id, $user_2->id]), json_encode($result));
    }
}