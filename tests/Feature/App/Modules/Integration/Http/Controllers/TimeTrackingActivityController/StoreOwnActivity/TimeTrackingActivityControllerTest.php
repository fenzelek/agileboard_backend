<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController\StoreOwnActivity;

use App\Models\Db\Company;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;
use Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController\TimeTrackingActivityControllerTrait;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;

class TimeTrackingActivityControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    use ResponseHelper;
    use TimeTrackingActivityControllerTrait;
    use ProjectHelper;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = factory(Company::class)->create();
        $this->project = $this->getProject($this->company, $this->user, RoleType::ADMIN);
        $this->ticket = $this->getTicket($this->project);
    }

    /**
     * @feature Time Tracking
     * @scenario Add Own Activity
     * @case Failed Adding Activity when sent invalid data
     *
     * @test
     */
    public function store_failed_adding_activities_when_sent_invalid_data()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        //WHEN
        $this->sendAddActivityRequest([
            'project_id',
            'ticket_id',
            'from',
            'to',
        ]);

        //THEN
        $this->verifyValidationResponse([
            'project_id',
            'ticket_id',
            'from',
            'to',
        ]);
    }

    /**
     * @feature Time Tracking
     * @scenario Add Own Activity
     * @case Failed add when sent not valid time
     *
     * @test
     */
    public function store_not_add_because_given_time_is_feature()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        //WHEN
        $this->sendAddActivityRequest([
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'from' => '3021-10-12 11:00:00',
            'to' => '3021-10-12 11:10:00',
        ]);

        //THEN
        $this->assertResponseStatus(424);
    }

    /**
     * @feature Time Tracking
     * @scenario Add Own Activity
     * @case Failed add when sent not valid 'to' time
     *
     * @test
     */
    public function store_not_add_because_given_time_to_is_feature()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        //WHEN
        $this->sendAddActivityRequest([
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'from' => '2021-10-12 11:00:00',
            'to' => '3021-10-12 11:10:00',
        ]);

        //THEN
        $this->assertResponseStatus(424);
    }

    /**
     * @feature Time Tracking
     * @scenario Add Own Activity
     * @case Failed add when company has not integration
     *
     * @test
     */
    public function store_not_add_because_company_has_not_manual_integration()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        //WHEN
        $this->sendAddActivityRequest([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'from' => '2021-10-12 11:00:00',
            'to' => '2021-10-12 11:10:00',
        ]);

        //THEN
        $this->assertResponseStatus(403);
    }

    /**
     * @feature Time Tracking
     * @scenario Add Own Activity
     * @case Activity added successful when sent valid data
     *
     * @test
     */
    public function store_add_manual_activity()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);
        $this->setManualIntegration();

        //WHEN
        $this->sendAddActivityRequest([
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'from' => '2021-10-12 11:00:00',
            'to' => '2021-10-12 11:10:00',
        ]);

        //THEN
        $this->assertResponseStatus(201);
    }

    /**
     * @feature Time Tracking
     * @scenario Add Own Activity
     * @case Activity added, old activity has same time, but deleted
     *
     * @test
     */
    public function store_add_manual_activity_old_activity_deleted()
    {
        //GIVEN
        $activity = $this->createDBActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $activity->delete();
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);
        $this->setManualIntegration();

        //WHEN
        $this->sendAddActivityRequest([
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'from' => '2021-10-01 11:00:00',
            'to' => '2021-10-01 11:10:00',
        ]);

        //THEN
        $this->assertResponseStatus(201);
    }

    /**
     * @feature Time Tracking
     * @scenario Add Own Activity
     * @case Activity wasnt added when not empty time slot found
     *
     * @test
     */
    public function store_not_add_because_not_empty_time_slot()
    {
        //GIVEN
        $this->createDBActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);
        $this->setManualIntegration();

        //WHEN
        $this->sendAddActivityRequest([
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'from' => '2021-10-01 11:00:00',
            'to' => '2021-10-01 11:10:00',
        ]);

        //THEN
        $this->assertResponseStatus(204);
    }

    private function sendAddActivityRequest(array $entry): void
    {
        $this->post(route('time-tracking-activity.store-own-activity', [
            'selected_company_id' => $this->company->id,
        ]), $entry);
    }
}
