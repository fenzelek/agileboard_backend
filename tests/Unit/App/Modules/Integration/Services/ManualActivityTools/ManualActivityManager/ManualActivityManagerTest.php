<?php

namespace Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityManager;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Modules\Integration\Exceptions\InvalidManualActivityTimePeriod;
use App\Modules\Integration\Exceptions\InvalidManualIntegrationForCompany;
use App\Modules\Integration\Services\ManualActivityManager;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Mockery as moc;
use Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityToolsTrait;

class ManualActivityManagerTest extends TestCase
{
    use DatabaseTransactions;
    use ManualActivityToolsTrait;

    /**
     * @var ManualActivityManager
     */
    private ManualActivityManager $manual_manager;

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

        $this->manual_manager = $this->app->make(ManualActivityManager::class);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity manager
     * @case failed, create new activity, has no integration
     *
     * @test
     */
    public function failed_company_has_no_manual_integration()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');

        //WHEN
        try {
            $this->manual_manager->addActivity($store_activity, $this->user);
        } catch (InvalidManualIntegrationForCompany $e) {

            //THEN
            $this->assertInstanceOf(InvalidManualIntegrationForCompany::class, $e);
        }
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity manager
     * @case failed, create new activity, time not current
     *
     * @test
     */
    public function failed_given_time_not_current()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:10:00', '2021-10-01 11:00:00');

        //WHEN
        try {
            $this->manual_manager->addActivity($store_activity, $this->user);
        } catch (InvalidManualActivityTimePeriod $e) {

            //THEN
            $this->assertInstanceOf(InvalidManualActivityTimePeriod::class, $e);
        }
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity manager
     * @case failed, create new activity, time is feature
     *
     * @test
     */
    public function failed_given_time_is_feature()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('3021-10-01 11:10:00', '3021-10-01 11:00:00');

        //WHEN
        try {
            $this->manual_manager->addActivity($store_activity, $this->user);
        } catch (InvalidManualActivityTimePeriod $e) {

            //THEN
            $this->assertInstanceOf(InvalidManualActivityTimePeriod::class, $e);
        }
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity manager
     * @case success, create new activity
     *
     * @test
     */
    public function success_all_current()
    {
        //GIVEN
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $store_activity = $this->getStoreActivity($from, $to);
        $integration = $this->setManualIntegration();

        //WHEN
        $response = $this->manual_manager->addActivity($store_activity, $this->user);

        //THEN
        $this->assertIsArray($response);
        $this->assertEquals($store_activity->from, $response[0]->utc_started_at);
        $this->assertEquals($store_activity->to, $response[0]->utc_finished_at);
        $this->assertDatabaseCount('time_tracking_manual_activity_history', 1);
        $this->assertActivityHistoryExist($store_activity);
        $this->assertDatabaseCount('time_tracking_activities', 1);
        $this->assertActivityExist($store_activity, $integration);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity manager
     * @case success, create new activity
     *
     * @test
     */
    public function success_add_new_activity_old_activity_same_time_was_deleted()
    {
        //GIVEN
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $this->setDeletedActivityState($from, $to);

        $store_activity = $this->getStoreActivity($from, $to);

        $integration = $this->setManualIntegration();

        //WHEN
        $response = $this->manual_manager->addActivity($store_activity, $this->user);

        //THEN
        $this->assertIsArray($response);
        $this->assertEquals($store_activity->from, $response[0]->utc_started_at);
        $this->assertEquals($store_activity->to, $response[0]->utc_finished_at);
        $this->assertDatabaseCount('time_tracking_manual_activity_history', 1);
        $this->assertActivityHistoryExist($store_activity);
        $this->assertDatabaseCount('time_tracking_activities', 2);
        $this->assertActivityExist($store_activity, $integration);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity manager
     * @case success, create new activity
     *
     * @test
     */
    public function failed_DB_error()
    {
        //GIVEN
        $db = moc::mock(Connection::class);
        $db->shouldReceive('transaction')->andThrow(\Exception::class);

        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->setManualIntegration();

        $this->manual_manager = $this->app->make(ManualActivityManager::class, ['db' => $db]);

        //WHEN
        $response = $this->manual_manager->addActivity($store_activity, $this->user);

        //THEN
        $this->assertEmpty($response);
        $this->assertDatabaseCount('time_tracking_manual_activity_history', 0);
        $this->assertDatabaseCount('time_tracking_activities', 0);
    }
}
