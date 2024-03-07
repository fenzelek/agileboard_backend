<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\UserMatcher;

use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\User;
use App\Modules\Integration\Services\TimeTracking\UserMatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;
use App\Models\Db\Integration\TimeTracking\User as TimeTrackingUser;
use App\Models\Db\Integration\TimeTracking\Activity as TimeTrackingActivity;

class ProcessTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @var Integration
     */
    protected $hubstaff_integration;

    /**
     * @inheritdoc
     */
    public function setUp():void
    {
        parent::setUp();

        $this->company = factory(Company::class)->create();

        $this->hubstaff_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
        ]);
    }

    /** @test */
    public function it_updates_valid_user_and_his_activities()
    {
        $user_matcher = app()->make(UserMatcher::class);

        $time_tracking_user = factory(TimeTrackingUser::class, 2)->create([
            'user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);

        $user_1 = factory(User::class)->create(
            ['email' => $time_tracking_user[0]->external_user_email]
        );
        $user_2 = factory(User::class)->create(
            ['email' => $time_tracking_user[1]->external_user_email]
        );

        $user_1_time_tracking_activities = factory(TimeTrackingActivity::class, 5)->create([
            'time_tracking_user_id' => $time_tracking_user[0]->id,
            'user_id' => null,
        ]);

        $user_2_time_tracking_activities = factory(TimeTrackingActivity::class, 5)->create([
            'time_tracking_user_id' => $time_tracking_user[1]->id,
            'user_id' => null,
        ]);

        $other_time_tracking_activities = factory(TimeTrackingActivity::class, 5)->create([
            'time_tracking_user_id' => $time_tracking_user[1]->id,
            'user_id' => $time_tracking_user[1]->id,
        ]);

        $this->assignUsersToCompany(collect([$user_1, $user_2]), $this->company);

        // initial assertions to make sure everything is as it should be
        $this->assertNull($time_tracking_user[0]->fresh()->user_id);
        $this->assertNull($time_tracking_user[1]->fresh()->user_id);
        $this->verifyTimeTrackingActivities($user_1_time_tracking_activities, null);
        $this->verifyTimeTrackingActivities($user_2_time_tracking_activities, null);
        $this->verifyTimeTrackingActivities(
            $other_time_tracking_activities,
            $time_tracking_user[1]->id
        );

        // act
        $result = $user_matcher->process($time_tracking_user[0]);

        // make sure result is as expected
        $this->assertTrue($result instanceof TimeTrackingUser);
        $this->assertSame($time_tracking_user[0]->id, $result->id);

        // those should be updated
        $updated_user = $time_tracking_user[0]->fresh();
        $this->assertEquals($user_1->id, $updated_user->user_id);
        $this->verifyTimeTrackingActivities($user_1_time_tracking_activities, $user_1->id);

        // those shouldn't be touched
        $this->assertNull($time_tracking_user[1]->fresh()->user_id);
        $this->verifyTimeTrackingActivities($user_2_time_tracking_activities, null);
        $this->verifyTimeTrackingActivities(
            $other_time_tracking_activities,
            $time_tracking_user[1]->id
        );
    }

    /** @test */
    public function it_wont_assign_user_assigned_to_other_company()
    {
        $user_matcher = app()->make(UserMatcher::class);

        $time_tracking_user = factory(TimeTrackingUser::class, 2)->create([
            'user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);

        $user_1 = factory(User::class)->create(
            ['email' => $time_tracking_user[0]->external_user_email]
        );

        $user_1_time_tracking_activities = factory(TimeTrackingActivity::class, 5)->create([
            'time_tracking_user_id' => $time_tracking_user[0]->id,
            'user_id' => null,
        ]);

        $other_company = factory(Company::class)->create();

        $this->assignUsersToCompany(collect([$user_1]), $other_company);

        // initial assertions to make sure everything is as it should be
        $this->assertNull($time_tracking_user[0]->fresh()->user_id);
        $this->verifyTimeTrackingActivities($user_1_time_tracking_activities, null);

        // act
        $result = $user_matcher->process($time_tracking_user[0]);

        // make sure result is as expected
        $this->assertNull($result);

        // nothing should be updated
        $this->assertNull($time_tracking_user[0]->fresh()->user_id);
        $this->verifyTimeTrackingActivities($user_1_time_tracking_activities, null);
    }

    /** @test */
    public function it_wont_change_anything_when_user_is_already_matched()
    {
        $user_matcher = app()->make(UserMatcher::class);

        $assigned_user_id = 91203;

        $time_tracking_user = factory(TimeTrackingUser::class, 2)->create([
            'user_id' => $assigned_user_id,
            'integration_id' => $this->hubstaff_integration->id,
        ]);

        $user_1 = factory(User::class)->create([
            'email' => $time_tracking_user[0]->external_user_email,
        ]);

        $user_1_time_tracking_activities = factory(TimeTrackingActivity::class, 5)->create([
            'time_tracking_user_id' => $time_tracking_user[0]->id,
            'user_id' => null,
        ]);

        $this->assignUsersToCompany(collect([$user_1]), $this->company);

        // initial assertions to make sure everything is as it should be
        $this->assertSame($assigned_user_id, $time_tracking_user[0]->fresh()->user_id);
        $this->verifyTimeTrackingActivities($user_1_time_tracking_activities, null);

        // act
        $result = $user_matcher->process($time_tracking_user[0]);

        // make sure result is as expected
        $this->assertNull($result);

        // nothing should be updated
        $this->assertSame($assigned_user_id, $time_tracking_user[0]->fresh()->user_id);
        $this->verifyTimeTrackingActivities($user_1_time_tracking_activities, null);
    }

    /** @test */
    public function it_wont_change_anything_when_cannot_match_user()
    {
        $user_matcher = app()->make(UserMatcher::class);

        $time_tracking_user = factory(TimeTrackingUser::class, 2)->create([
            'user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);

        $user_1_time_tracking_activities = factory(TimeTrackingActivity::class, 5)->create([
            'time_tracking_user_id' => $time_tracking_user[0]->id,
            'user_id' => null,
        ]);

        // initial assertions to make sure everything is as it should be
        $this->assertNull($time_tracking_user[0]->fresh()->user_id);
        $this->verifyTimeTrackingActivities($user_1_time_tracking_activities, null);

        // act
        $result = $user_matcher->process($time_tracking_user[0]);

        // make sure result is as expected
        $this->assertNull($result);

        // nothing should be updated
        $this->assertNull($time_tracking_user[0]->fresh()->user_id);
        $this->verifyTimeTrackingActivities($user_1_time_tracking_activities, null);
    }

    protected function verifyTimeTrackingActivities(Collection $activities, $expected_user_id)
    {
        $activities->each(function ($activity) use ($expected_user_id) {
            $this->assertSame($expected_user_id, $activity->fresh()->user_id);
        });
    }
}
