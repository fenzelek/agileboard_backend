<?php

namespace Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityValidator;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Integration\Services\ManualActivityTools\ManualActivityValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityToolsTrait;

class ManualActivityValidatorTest extends TestCase
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
     * @var ManualActivityValidator
     */
    private ManualActivityValidator $validator;

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
            $this->app->make(ManualActivityValidator::class);

        $this->setManualIntegration();
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity
     * @case correct
     *
     * @test
     */
    public function validate_correct()
    {
        //GIVEN
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $activity =
            $this->createDBActivityIntegration(
                $this->other_user,
                $from,
                $to,
                $this->manual_integration->id,
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
        $remove_activities_provider = $this->getRemoveActivities([$activity->id]);

        //WHEN
        $response = $this->validator->validate($remove_activities_provider);

        //THEN
        $this->assertFalse($response);
    }
}
