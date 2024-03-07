<?php

namespace Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualOwnActivityValidator;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Integration\Services\ManualActivityTools\ManualOwnActivityValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityToolsTrait;

class ManualOwnActivityValidatorTest extends TestCase
{
    use DatabaseTransactions;
    use ManualActivityToolsTrait;

    /**
     * @var Project
     */
    private Project $project;

    /**
     * @var Ticket
     */
    private Ticket $ticket;

    /**
     * @var ManualOwnActivityValidator
     */
    private ManualOwnActivityValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->user->setSelectedCompany($this->company->id);
        $this->project = $this->getProject($this->company, $this->user, RoleType::DEVELOPER);
        $this->ticket = $this->getTicket($this->project);

        $this->other_user = factory(User::class)->create();
        $this->other_project =
            $this->getProject($this->company, $this->other_user, RoleType::DEVELOPER);

        $this->validator =
            $this->app->make(ManualOwnActivityValidator::class);
        $this->validator->forUser($this->user);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity
     * @case success, given ids belong to the user
     *
     * @test
     */
    public function success_ids_belong_user()
    {
        //GIVEN
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $integration = $this->setManualIntegration();
        $activity = $this->createDBActivityIntegration(
            $this->user,
            $from,
            $to,
            $integration->id,
            'manual123'
        );
        $remove_activities_provider = $this->getRemoveActivities([$activity->id]);

        //WHEN
        $response = $this->validator->validate($remove_activities_provider);

        //THEN
        $this->assertTrue($response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity
     * @case failed, given ids not belong to the user
     *
     * @test
     */
    public function failed_ids_not_belong_user()
    {
        //GIVEN
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $integration = $this->setManualIntegration();
        $activity =
            $this->createDBActivityIntegration(
                $this->other_user,
                $from,
                $to,
                $integration->id,
                'manual123'
            );
        $remove_activities_provider = $this->getRemoveActivities([$activity->id]);

        //WHEN
        $response = $this->validator->validate($remove_activities_provider, $this->user);

        //THEN
        $this->assertFalse($response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity
     * @case failed, given activity from TimeTracker
     *
     * @test
     */
    public function failed_activity_for_remove_is_not_manual()
    {
        //GIVEN
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $integration = $this->setManualIntegration();
        $activity =
            $this->createDBActivityIntegration(
                $this->other_user,
                $from,
                $to,
                $integration->id,
                'tt123'
            );
        $remove_activities_provider = $this->getRemoveActivities([$activity->id], $this->user->id);

        //WHEN
        $response = $this->validator->validate($remove_activities_provider, $this->user);

        //THEN
        $this->assertFalse($response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity
     * @case failed, one of given activity from TimeTracker
     *
     * @test
     */
    public function failed_one_of_activity_for_remove_is_not_manual()
    {
        //GIVEN
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $integration = $this->setManualIntegration();
        $activity_one =
            $this->createDBActivityIntegration(
                $this->other_user,
                $from,
                $to,
                $integration->id,
                'manual123'
            );
        $activity_two =
            $this->createDBActivityIntegration(
                $this->other_user,
                $from,
                $to,
                $integration->id,
                'tt123'
            );
        $remove_activities_provider =
            $this->getRemoveActivities([$activity_one->id, $activity_two->id], $this->user->id);

        //WHEN
        $response = $this->validator->validate($remove_activities_provider, $this->user);

        //THEN
        $this->assertFalse($response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity
     * @case failed, company integration not correct
     *
     * @test
     */
    public function failed_integration_company_not_correct()
    {
        //GIVEN
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $company = factory(Company::class)->create();

        $integration = $this->setTimeTrackerIntegration($company);
        $activity =
            $this->createDBActivityIntegration(
                $this->other_user,
                $from,
                $to,
                $integration->id,
                'manual123'
            );
        $remove_activities_provider = $this->getRemoveActivities([$activity->id], $this->user->id);

        //WHEN
        $response = $this->validator->validate($remove_activities_provider, $this->user);

        //THEN
        $this->assertFalse($response);
    }
}
