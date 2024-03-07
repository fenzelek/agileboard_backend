<?php

namespace Tests\Unit\App\Modules\Integration\Controllers;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Modules\Integration\Exceptions\InvalidManualIntegrationForCompany;
use App\Modules\Integration\Http\Controllers\TimeTrackingActivityController;
use App\Modules\Integration\Services\Factories\ManualRemoveActivityManagerFactory;
use App\Modules\Integration\Services\ManualRemoveActivityManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityToolsTrait;
use Mockery as moc;

class TimeTrackingActivityControllerTest extends TestCase
{
    use DatabaseTransactions;
    use ManualActivityToolsTrait;

    /**
     * @var TimeTrackingActivityController
     */
    private TimeTrackingActivityController $tracker_controller;

    /**
     * @var Project
     */
    private Project $project;

    /**
     * @var Ticket
     */
    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->user->setSelectedCompany($this->company->id);
        $this->project = $this->getProject($this->company, $this->user, RoleType::DEVELOPER);
        $this->ticket = $this->getTicket($this->project);

        $this->tracker_controller = $this->app->make(TimeTrackingActivityController::class);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity controller
     * @case failed, remove activity, no integration
     *
     * @test
     */
    public function failed_company_has_no_integration()
    {
        //GIVEN
        $activity_manager_factory = $this->makeActivityManagerFactoryAndException(InvalidManualIntegrationForCompany::class);

        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $activity = $this->createDBActivityIntegration($this->user, $from, $to, -456, 'manual123');
        $remove_activities_provider =
            $this->getRemoveActivities([$activity->id]);

        //WHEN
        $response = $this->tracker_controller->removeActivities(
            $remove_activities_provider,
            $activity_manager_factory
        );

        //THEN
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity controller
     * @case failed, remove activity, DB error
     *
     * @test
     */
    public function failed_not_remove_DB_exception()
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

        $activity_manager_factory = $this->makeActivityManagerFactory([$activity->id]);
        $remove_activities_provider =
            $this->getRemoveActivities([$activity->id]);

        //WHEN
        $response = $this->tracker_controller->removeActivities(
            $remove_activities_provider,
            $activity_manager_factory
        );

        //THEN
        $this->assertEquals(405, $response->getStatusCode());
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity controller
     * @case success, remove activity
     *
     * @test
     */
    public function success_remove_activity()
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
        $activity_manager_factory = $this->makeActivityManagerFactory([]);
        $remove_activities_provider =
            $this->getRemoveActivities([$activity->id]);

        //WHEN
        $response = $this->tracker_controller->removeActivities(
            $remove_activities_provider,
            $activity_manager_factory
        );

        //THEN
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @return ManualRemoveActivityManagerFactory|moc\LegacyMockInterface|moc\MockInterface
     */
    private function makeActivityManagerFactoryAndException($exception_type)
    {
        $activity_manager_factory = moc::mock(ManualRemoveActivityManagerFactory::class);
        $activity_manager = moc::mock(ManualRemoveActivityManager::class);
        $activity_manager_factory->shouldReceive('create')
            ->once()
            ->andReturn($activity_manager);
        $activity_manager->shouldReceive('removeActivities')
            ->andThrow($exception_type);

        return $activity_manager_factory;
    }

    /**
     * @return ManualRemoveActivityManagerFactory|moc\LegacyMockInterface|moc\MockInterface
     */
    private function makeActivityManagerFactory($activity_ids)
    {
        $activity_manager = moc::mock(ManualRemoveActivityManager::class);

        $activity_manager_factory = moc::mock(ManualRemoveActivityManagerFactory::class);
        $activity_manager = moc::mock(ManualRemoveActivityManager::class);
        $activity_manager_factory->shouldReceive('create')
            ->once()
            ->andReturn($activity_manager);

        $activity_manager->shouldReceive('removeActivities')->andReturn($activity_ids);

        return $activity_manager_factory;
    }
}
