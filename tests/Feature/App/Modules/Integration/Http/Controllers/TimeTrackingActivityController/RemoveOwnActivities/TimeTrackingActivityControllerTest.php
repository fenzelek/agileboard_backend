<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController\RemoveOwnActivities;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;
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
     * @var Project
     */
    protected $project;

    /**
     * @var Ticket
     */
    protected $ticket;

    /**
     * @var User
     */
    protected $other_user;

    public function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->project = $this->getProject($this->company, $this->user, RoleType::ADMIN);
        $this->ticket = $this->getTicket($this->project);

        $this->other_user = factory(User::class)->create();
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Own Manual Activity
     * @case Failed Remove Activity when sent invalid data
     *
     * @test
     */
    public function remove_failed_activities_when_sent_invalid_data()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        $this->sendRemoveActivitiesRequest([
            'activities',
        ]);

        $this->verifyValidationResponse([
            'activities',
        ]);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Own Manual Activity
     * @case Failed Remove Activity
     *
     * @test
     */
    public function failed_remove_because_company_has_not_manual_integration()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $integration = $this->setTimeTrackerIntegration();
        $activity = $this->createDBActivityIntegration(
            $this->user,
            '2021-10-01 11:00:00',
            '2021-10-01 11:10:00',
            $integration->id,
            'manual123'
        );

        //WHEN
        $this->sendRemoveActivitiesRequest([
            'activities' => [$activity->id],
        ]);

        //THEN
        $this->assertResponseStatus(403);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Own Manual Activity
     * @case Failed Remove Activity, activity different user given
     *
     * @test
     */
    public function failed_remove_two_activities_different_user()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);
        $this->assignUsersToCompany(
            collect([$this->other_user]),
            $this->company,
            RoleType::DEVELOPER
        );

        $integration_manual = $this->setManualIntegration();
        $activity =
            $this->createDBActivityIntegration(
                $this->other_user,
                '2021-10-01 11:00:00',
                '2021-10-01 11:10:00',
                $integration_manual->id,
                'manual123'
            );

        //WHEN
        $this->sendRemoveActivitiesRequest([
            'activities' => [$activity->id],
        ]);

        //THEN
        $this->assertResponseStatus(405);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Own Manual Activity
     * @case Failed Remove Activity, user not belongs current company
     *
     * @test
     */
    public function failed_remove_user_not_in_current_company()
    {
        //GIVEN
        $integration = $this->setManualIntegration();
        $activity =
            $this->createDBActivityIntegration(
                $this->user,
                '2021-10-01 11:00:00',
                '2021-10-01 11:10:00',
                $integration->id,
                'manual123'
            );

        //WHEN
        $this->sendRemoveActivitiesRequest([
            'activities' => [$activity->id],
        ]);

        //THEN
        $this->assertResponseStatus(401);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Own Manual Activity
     * @case Failed Remove Activity, company without integration
     *
     * @test
     */
    public function failed_remove_company_without_manual_integration()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);
        $activity =
            $this->createDBActivityIntegration(
                $this->user,
                '2021-10-01 11:00:00',
                '2021-10-01 11:10:00',
                -456,
                'manual123'
            );

        //WHEN
        $this->sendRemoveActivitiesRequest([
            'activities' => [$activity->id],
        ]);

        //THEN
        $this->assertResponseStatus(403);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Own Manual Activity
     * @case Failed remove activities, one activity different user
     *
     * @test
     */
    public function failed_remove_two_activities()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

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
                $this->other_user,
                '2021-10-01 11:10:00',
                '2021-10-01 11:20:00',
                $integration->id,
                'manual123'
            );

        //WHEN
        $this->sendRemoveActivitiesRequest([
            'activities' => [$activity_one->id, $activity_two->id],
        ]);

        //THEN
        $this->assertResponseStatus(405);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Own Manual Activity
     * @case Success remove activities
     *
     * @test
     */
    public function success_remove_two_activities()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

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
                '2021-10-01 11:10:00',
                '2021-10-01 11:20:00',
                $integration->id,
                'manual123'
            );

        //WHEN
        $this->sendRemoveActivitiesRequest([
            'activities' => [$activity_one->id, $activity_two->id],
        ]);

        //THEN
        $this->assertResponseStatus(204);
    }

    private function sendRemoveActivitiesRequest(array $entry): void
    {
        $this->delete(route('time-tracking-activity.remove-own-activities', [
            'selected_company_id' => $this->company->id,
        ]), $entry);
    }
}
