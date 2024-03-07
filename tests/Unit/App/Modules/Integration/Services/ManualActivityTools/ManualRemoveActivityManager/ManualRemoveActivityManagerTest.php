<?php

namespace Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualRemoveActivityManager;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Integration\Exceptions\InvalidIdsException;
use App\Modules\Integration\Exceptions\InvalidManualIntegrationForCompany;
use App\Modules\Integration\Services\ManualActivityTools\ManualOwnActivityValidator;
use App\Modules\Integration\Services\ManualRemoveActivityManager;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityToolsTrait;
use Mockery as moc;

class ManualRemoveActivityManagerTest extends TestCase
{
    use DatabaseTransactions;
    use ManualActivityToolsTrait;

    /**
     * @var ManualRemoveActivityManager
     */
    private ManualRemoveActivityManager $manual_remover;

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
    private $activity_validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->user->setSelectedCompany($this->company->id);
        $this->project = $this->getProject($this->company, $this->user, RoleType::DEVELOPER);
        $this->ticket = $this->getTicket($this->project);

        $this->activity_validator = $this->app->make(ManualOwnActivityValidator::class);
        $this->manual_remover = $this->app->make(ManualRemoveActivityManager::class, [
            'activity_validator' => $this->activity_validator,
        ]);
        $this->activity_validator->forUser($this->user);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity manager
     * @case failed, remove activity, DB error
     *
     * @test
     */
    public function failed_Db_error_activity_not_removed()
    {
        //GIVEN
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $db = moc::mock(Connection::class);
        $db->shouldReceive('transaction')->andThrow(\Exception::class);

        $integration = $this->setManualIntegration();
        $activity = $this->createDBActivityIntegration(
            $this->user,
            $from,
            $to,
            $integration->id,
            'manual123'
        );
        $remove_activities_provider =
            $this->getRemoveActivities([$activity->id]);

        $this->manual_remover = $this->app->make(ManualRemoveActivityManager::class, ['db' => $db, 'activity_validator' => $this->activity_validator]);

        //WHEN
        $response =
            $this->manual_remover->removeActivities($remove_activities_provider);

        //THEN
        $this->assertIsArray($response);
        $this->assertCount(1, $response);
        $this->assertDatabaseCount('time_tracking_activities', 1);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity manager
     * @case failed, company has no integration
     *
     * @test
     */
    public function failed_company_has_no_integration()
    {
        //GIVEN
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $activity = $this->createDBActivityIntegration($this->user, $from, $to, -456, 'manual123');
        $remove_activities_provider =
            $this->getRemoveActivities([$activity->id]);

        //WHEN
        try {
            $this->manual_remover->removeActivities($remove_activities_provider);
        } catch (InvalidManualIntegrationForCompany $e) {

            //THEN
            $this->assertInstanceOf(InvalidManualIntegrationForCompany::class, $e);
        }
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity manager
     * @case Failed, wrong user given, activities not belongs user
     *
     * @test
     */
    public function failed_removed_activity_when_given_wrong_user()
    {
        //GIVEN
        $wrong_user = factory(User::class)->create();
        $from = '2021-10-01 11:00:00';
        $to = '2021-10-01 11:10:00';
        $integration = $this->setManualIntegration();
        $activity = $this->createDBActivityIntegration(
            $wrong_user,
            $from,
            $to,
            $integration->id,
            'manual123'
        );
        $remove_activities_provider =
            $this->getRemoveActivities([$activity->id]);

        //WHEN
        try {
            $this->manual_remover->removeActivities($remove_activities_provider);
        } catch (InvalidIdsException $e) {

            //THEN
            $this->assertInstanceOf(InvalidIdsException::class, $e);
        }
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity manager
     * @case success, activity removed
     *
     * @test
     */
    public function success_removed_activity()
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
        $remove_activities_provider =
            $this->getRemoveActivities([$activity->id]);

        //WHEN
        $response =
            $this->manual_remover->removeActivities($remove_activities_provider);

        //THEN
        $this->assertIsArray($response);
        $this->assertCount(0, $response);
        $this->assertSoftDeleted('time_tracking_activities', ['id' => $activity->id]);
        $this->assertDatabaseCount('time_tracking_activities', 1);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual remove activity manager
     * @case success, activity removed
     *
     * @test
     */
    public function success_removed_many_activity()
    {
        //GIVEN
        $integration = $this->setManualIntegration();
        $activity_one =
            $this->createDBActivityIntegration(
                $this->user,
                '2021-10-01 11:00:00',
                '2021-10-01 11:10:00',
                $integration->id,
                'manual123'
            );
        $activity_two =
            $this->createDBActivityIntegration(
                $this->user,
                '2021-10-01 11:20:00',
                '2021-10-01 11:30:00',
                $integration->id,
                'manual123'
            );
        $activity_three =
            $this->createDBActivityIntegration(
                $this->user,
                '2021-10-01 11:30:00',
                '2021-10-01 11:40:00',
                $integration->id,
                'manual123'
            );
        $remove_activities_provider =
            $this->getRemoveActivities(
                [$activity_one->id, $activity_two->id, $activity_three->id],
                $this->user->id
            );

        //WHEN
        $response =
            $this->manual_remover->removeActivities($remove_activities_provider);

        //THEN
        $this->assertIsArray($response);
        $this->assertCount(0, $response);
        $this->assertSoftDeleted('time_tracking_activities', ['id' => $activity_one->id]);
        $this->assertSoftDeleted('time_tracking_activities', ['id' => $activity_two->id]);
        $this->assertSoftDeleted('time_tracking_activities', ['id' => $activity_three->id]);
        $this->assertDatabaseCount('time_tracking_activities', 3);
    }
}
