<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController;

use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;

class BulkUpdateTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ResponseHelper, ProjectHelper;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @var Integration
     */
    protected $hubstaff_integration;

    /**
     * @var Integration
     */
    protected $upwork_integration;

    /**
     * @inheritdoc
     */
    public function setUp():void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = factory(Company::class)->create();

        $this->hubstaff_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
        ]);

        $this->upwork_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::UPWORK)->id,
        ]);
    }

    /** @test */
    public function it_gets_validation_error_when_activity_doesnt_exist()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $activity =
            factory(Activity::class)->create(['integration_id' => $this->hubstaff_integration->id]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activity->id + 1,
                ],
                [
                    'id' => $activity->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(['activities.0.id'], ['activities.1.id']);
    }

    /** @test */
    public function it_gets_validation_error_when_sending_same_record_twice()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $activity =
            factory(Activity::class)->create(['integration_id' => $this->hubstaff_integration->id]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activity->id,
                ],
                [
                    'id' => $activity->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(['activities.0.id', 'activities.1.id']);
    }

    /** @test */
    public function it_gets_validation_error_when_activity_belongs_to_other_company_integration()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $activities = factory(Activity::class, 2)->create([
            'integration_id' => $this->hubstaff_integration->id,
        ]);
        $activities[1]->update(['integration_id' => factory(Integration::class)->create()->id]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                ],
                [
                    'id' => $activities[1]->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(['activities.1.id'], ['activities.0.id']);
    }

    /** @test */
    public function it_gets_validation_error_when_record_belongs_to_other_user_when_regular_user()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        $activities = factory(Activity::class, 2)->create([
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);
        $activities[1]->update(['user_id' => factory(User::class)->create()->id]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                ],
                [
                    'id' => $activities[1]->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(['activities.1.id'], ['activities.0.id']);
    }

    /** @test */
    public function it_doesnt_get_id_error_when_record_belongs_to_other_user_when_owner()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::OWNER);

        $activities = factory(Activity::class, 2)->create([
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);
        $activities[1]->update(['user_id' => factory(User::class)->create()->id]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                ],
                [
                    'id' => $activities[1]->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(null, ['activities.0.id', 'activities.1.id']);
    }

    /** @test */
    public function it_gets_validation_error_when_record_is_locked_when_regular_user()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        $activities = factory(Activity::class, 2)->create([
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);
        $activities[0]->update(['locked_user_id' => factory(User::class)->create()->id]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                ],
                [
                    'id' => $activities[1]->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(['activities.0.id'], ['activities.1.id']);
    }

    /** @test */
    public function it_doesnt_get_id_error_when_record_locked_when_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $activities = factory(Activity::class, 2)->create([
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);
        $activities[0]->update(['locked_user_id' => factory(User::class)->create()->id]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                ],
                [
                    'id' => $activities[1]->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(null, ['activities.0.id', 'activities.1.id']);
    }

    /** @test */
    public function it_doesnt_get_error_when_activity_belongs_to_other_user_when_owner()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::OWNER);

        $activities = factory(Activity::class, 2)->create([
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);
        $activities[1]->update(['user_id' => factory(User::class)->create()->id]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                ],
                [
                    'id' => $activities[1]->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(null, ['activities.0.id', 'activities.1.id']);
    }

    /** @test */
    public function it_gets_error_when_activity_belongs_to_other_user_when_developer()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        $activities = factory(Activity::class, 2)->create([
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);
        $activities[1]->update(['user_id' => factory(User::class)->create()->id]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                ],
                [
                    'id' => $activities[1]->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(['activities.1.id'], ['activities.0.id']);
    }

    /** @test */
    public function it_gets_error_when_project_belongs_to_other_company_when_owner()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::OWNER);

        $projects = factory(Project::class, 2)->create(['company_id' => $this->company->id]);
        $projects[1]->update(['company_id' => factory(Company::class)->create()->id]);

        $activities = factory(Activity::class, 2)->create([
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);
        $activities[1]->update(['user_id' => factory(User::class)->create()->id]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                    'project_id' => $projects[0]->id,
                    'ticket_id' => null,
                ],
                [
                    'id' => $activities[1]->id,
                    'project_id' => $projects[1]->id,
                    'ticket_id' => null,
                ],
            ],
        ]);

        $this->verifyValidationResponse(['activities.1.project_id'], [
            'activities.0.id',
            'activities.1.id',
            'activities.0.project_id',
            'activities.0.ticket_id',
            'activities.1.ticket_id',
        ]);
    }

    /** @test */
    public function it_gets_error_when_user_users_project_he_doesnt_belongs_to_when_regular_user()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        $projects = factory(Project::class, 3)->create(['company_id' => $this->company->id]);
        $this->setProjectRole($projects[1], RoleType::DEVELOPER);
        $this->setProjectRole($projects[2], RoleType::ADMIN);

        $activities = factory(Activity::class, 3)->create([
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                    'project_id' => $projects[0]->id,
                    'ticket_id' => null,
                ],
                [
                    'id' => $activities[1]->id,
                    'project_id' => $projects[1]->id,
                    'ticket_id' => null,
                ],
                [
                    'id' => $activities[2]->id,
                    'project_id' => $projects[2]->id,
                    'ticket_id' => null,
                ],
            ],
        ]);

        $this->verifyValidationResponse(['activities.0.project_id'], [
            'activities.0.id',
            'activities.1.id',
            'activities.1.project_id',
            'activities.0.ticket_id',
            'activities.1.ticket_id',
        ]);
    }

    /** @test */
    public function it_gets_error_when_ticket_doesnt_belong_to_same_project()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        $projects = factory(Project::class, 2)->create();

        $tickets = factory(Ticket::class, 3)->create(['project_id' => $projects[0]->id]);
        $tickets[1]->update(['project_id' => $projects[1]->id]);

        $activities = factory(Activity::class, 3)->create([
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                    'project_id' => $projects[0]->id,
                    'ticket_id' => $tickets[0]->id,
                ],
                [
                    'id' => $activities[1]->id,
                    'project_id' => $projects[1]->id,
                    'ticket_id' => $tickets[0]->id,
                ],
                [
                    'id' => $activities[2]->id,
                    'project_id' => $projects[1]->id,
                    'ticket_id' => $tickets[1]->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(
            ['activities.1.ticket_id'],
            ['activities.0.ticket_id', 'activities.2.ticket_id']
        );
    }

    /** @test */
    public function it_doesnt_get_error_when_project_and_ticket_set_to_null()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::OWNER);

        $projects = factory(Project::class, 2)->create(['company_id' => $this->company->id]);

        $tickets = factory(Ticket::class, 3)->create(['project_id' => $projects[0]->id]);
        $tickets[1]->update(['project_id' => $projects[1]->id]);

        $activities = factory(Activity::class, 3)->create([
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'integration_id' => $this->hubstaff_integration->id,
        ]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                    'project_id' => null,
                    'ticket_id' => null,
                ],
                [
                    'id' => $activities[1]->id,
                    'project_id' => $projects[1]->id,
                    'ticket_id' => $tickets[0]->id,
                ],
                [
                    'id' => $activities[2]->id,
                    'project_id' => null,
                    'ticket_id' => null,
                ],
            ],
        ]);

        $this->verifyValidationResponse(['activities.1.ticket_id'], [
            'activities.0.ticket_id',
            'activities.2.ticket_id',
            'activities.0.project_id',
            'activities.2.project_id',
            'activities.1.project_id',
        ]);
    }

    /** @test */
    public function it_gets_validation_error_when_no_locked_sent_by_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $activities = factory(Activity::class, 2)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'user_id' => $this->user->id,
            'locked_user_id' => null,
        ]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                    'project_id' => 'abc',
                ],
                [
                    'id' => $activities[1]->id,
                    'project_id' => 'def',
                ],
            ],
        ]);

        $this->verifyValidationResponse(
            ['activities.0.locked', 'activities.1.locked'],
            ['activities.0.id', 'activities.1.id']
        );
    }

    /** @test */
    public function it_doesnt_require_locked_when_developer()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        $activities = factory(Activity::class, 2)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'user_id' => $this->user->id,
            'locked_user_id' => null,
        ]);

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, [
            'activities' => [
                [
                    'id' => $activities[0]->id,
                    'project_id' => 'abc',
                ],
                [
                    'id' => $activities[1]->id,
                    'project_id' => 'def',
                ],
            ],
        ]);

        $this->verifyValidationResponse(
            null,
            ['activities.0.id', 'activities.1.id', 'activities.0.locked', 'activities.1.locked']
        );
    }

    /** @test */
    public function it_saves_records_when_developer()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        $projects = factory(Project::class, 2)->create(['company_id' => $this->company->id]);
        $this->setProjectRole($projects[0], RoleType::DEVELOPER);
        $this->setProjectRole($projects[1], RoleType::DEVELOPER);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $tickets = factory(Ticket::class, 3)->create(['project_id' => $projects[0]->id]);

        $activities = factory(Activity::class, 4)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'user_id' => $this->user->id,
            'locked_user_id' => null,
            'project_id' => 513,
        ]);

        $activities = $activities->map(function ($activity) {
            return $activity->fresh();
        });

        $data = [
            [
                'id' => $activities[0]->id,
                'integration_id' => 15,
                'user_id' => 12,
                'project_id' => $projects[0]->id,
                'ticket_id' => $tickets[0]->id,
                'external_activity_id' => 'abc',
                'time_tracking_user_id' => 981,
                'time_tracking_project_id' => 982,
                'time_tracking_note_id' => 983,
                'utc_started_at' => Carbon::now()->toDateTimeString(),
                'utc_finished_at' => Carbon::now()->addDays(10)->toDateTimeString(),
                'tracked' => 90,
                'activity' => 300,
                'locked' => true,
                'comment' => '',
            ],
            [
                'id' => $activities[1]->id,
                'project_id' => $projects[1]->id,
                'ticket_id' => null,
                'comment' => 'Sample comment',
                'locked' => false,
            ],
            [
                'id' => $activities[3]->id,
                'project_id' => null,
                'ticket_id' => null,
                'comment' => 'Not assigned to any project any more',
            ],
        ];

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, ['activities' => $data])->seeStatusCode(200);

        $this->verifyModifiedRecord($activities[0], $data[0], $now, null);
        $this->verifyModifiedRecord($activities[1], $data[1], $now, null);
        $this->assertSame($activities[2]->toArray(), $activities[2]->fresh()->toArray());
        $this->verifyModifiedRecord($activities[3], $data[2], $now, null);
    }

    /** @test */
    public function it_saves_records_when_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $projects = factory(Project::class, 2)->create(['company_id' => $this->company->id]);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $tickets = factory(Ticket::class, 3)->create(['project_id' => $projects[0]->id]);

        $other_user = factory(User::class)->create();

        $activities = factory(Activity::class, 4)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'user_id' => $this->user->id,
            'locked_user_id' => $other_user->id,
            'project_id' => 513,
        ]);
        $activities[0]->update(['locked_user_id' => null]);

        $activities = $activities->map(function ($activity) {
            return $activity->fresh();
        });

        $data = [
            [
                'id' => $activities[0]->id,
                'integration_id' => 15,
                'user_id' => 12,
                'project_id' => $projects[0]->id,
                'ticket_id' => $tickets[0]->id,
                'external_activity_id' => 'abc',
                'time_tracking_user_id' => 981,
                'time_tracking_project_id' => 982,
                'time_tracking_note_id' => 983,
                'utc_started_at' => Carbon::now()->toDateTimeString(),
                'utc_finished_at' => Carbon::now()->addDays(10)->toDateTimeString(),
                'tracked' => 90,
                'activity' => 300,
                'locked' => true,
                'comment' => '',
            ],
            [
                'id' => $activities[1]->id,
                'project_id' => $projects[1]->id,
                'ticket_id' => null,
                'comment' => 'Sample comment',
                'locked' => false,
            ],
            [
                'id' => $activities[3]->id,
                'project_id' => null,
                'ticket_id' => null,
                'comment' => 'Not assigned to any project any more',
                'locked' => true,
            ],
        ];

        $this->put('/integrations/time_tracking/activities/' . '?selected_company_id=' .
            $this->company->id, ['activities' => $data])->seeStatusCode(200);

        $this->verifyModifiedRecord($activities[0], $data[0], $now, $this->user->id);
        $this->verifyModifiedRecord($activities[1], $data[1], $now, null);
        $this->assertSame($activities[2]->toArray(), $activities[2]->fresh()->toArray());
        $this->verifyModifiedRecord($activities[3], $data[2], $now, $other_user->id);
    }

    protected function verifyModifiedRecord(Activity $activity, array $data, Carbon $now, $locked_user_id)
    {
        $this->assertEquals(
            array_except(
                $activity->toArray(),
                ['project_id', 'ticket_id', 'comment', 'updated_at', 'locked_user_id']
            ) +
            array_only($data, ['project_id', 'ticket_id', 'comment']) +
            ['updated_at' => $now->toDateTimeString(), 'locked_user_id' => $locked_user_id],
            Activity::find($activity->id)->toArray()
        );
    }
}
