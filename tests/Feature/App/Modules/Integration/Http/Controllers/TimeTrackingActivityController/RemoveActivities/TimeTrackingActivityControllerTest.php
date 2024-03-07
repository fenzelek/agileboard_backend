<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController\RemoveActivities;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
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
     * @var User
     */
    protected $other_user;

    public function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->other_user = factory(User::class)->create();

        $this->company = factory(Company::class)->create();
        $this->project = $this->getProject($this->company, $this->user, RoleType::ADMIN);
        $this->ticket = $this->getTicket($this->project);
    }

    /**
     * @feature Time Tracking
     * @scenario Add Manual Activity
     * @case Failed Adding Activity when user is not admin or owner
     *
     * @test
     */
    public function remove_failed_activities_when_userIsNotAdminOrOwner()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        //WHEN
        $this->sendRemoveActivitiesRequest([]);

        //THEN
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Manual Activity
     * @case Failed Remove Activity when sent invalid data
     *
     * @test
     */
    public function remove_failed_activities_when_sent_invalid_data()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        //WHEN
        $this->sendRemoveActivitiesRequest([
            'user_id',
            'activities',
        ]);

        //THEN
        $this->verifyValidationResponse([
            'activities',
        ]);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Manual Activity
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
            'user_id' => $this->user->id,
            'activities' => [$activity->id],
        ]);

        //THEN
        $this->assertResponseStatus(403);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Manual Activity
     * @case Failed Remove Activities, activities not exists
     *
     * @test
     */
    public function failed_remove_two_activities_user_not_belongs_current_company()
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
        $deleted_activity_id = $activity->id;
        \DB::table('time_tracking_activities')->where('id', $deleted_activity_id)->delete();
        $this->sendRemoveActivitiesRequest([
            'activities' => [$deleted_activity_id],
        ]);

        //THEN
        $this->assertResponseStatus(422);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Manual Activity
     * @case Success remove self activities
     *
     * @test
     */
    public function remove_self_activities_success()
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
                'manual124'
            );

        //WHEN
        $this->sendRemoveActivitiesRequest([
            'user_id' => $this->user->id,
            'activities' => [$activity_one->id, $activity_two->id],
        ]);

        //THEN
        $this->assertResponseStatus(204);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Manual Activity
     * @case Success remove activities, activities different user given
     *
     * @test
     */
    public function remove_two_activities_different_user()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);
        $this->assignUsersToCompany(
            collect([$this->other_user]),
            $this->company,
            RoleType::DEVELOPER
        );

        $integration = $this->setManualIntegration();
        $activity_one =
            $this->createDBActivityIntegration(
                $this->other_user,
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
            'user_id' => $this->other_user->id,
            'activities' => [$activity_one->id, $activity_two->id],
        ]);

        //THEN
        $this->assertResponseStatus(204);
    }

    /**
     * @feature Time Tracking
     * @scenario Remove Manual Activity
     * @case Success Remove Activity, activity different user given
     *
     * @test
     */
    public function success_remove_two_activities_different_type_activity()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);
        $this->assignUsersToCompany(
            collect([$this->other_user]),
            $this->company,
            RoleType::DEVELOPER
        );

        $integration_manual = $this->setManualIntegration();
        $integration = $this->setTimeTrackerIntegration();
        $activity_one =
            $this->createDBActivityIntegration(
                $this->other_user,
                '2021-10-01 11:00:00',
                '2021-10-01 11:10:00',
                $integration_manual->id,
                'manual123'
            );
        $activity_two =
            $this->createDBActivityIntegration(
                $this->other_user,
                '2021-10-01 11:10:00',
                '2021-10-01 11:20:00',
                $integration->id,
                'tt123'
            );

        //WHEN
        $this->sendRemoveActivitiesRequest([
            'user_id' => $this->other_user->id,
            'activities' => [$activity_one->id, $activity_two->id],
        ]);

        //THEN
        $this->assertResponseStatus(204);
    }

    private function sendRemoveActivitiesRequest(array $entry)
    {
        return $this->delete(route('time-tracking-activity.remove-activities', [
            'selected_company_id' => $this->company->id,
        ]), $entry);
    }
}
