<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use App\Models\Db\Integration\TimeTracking\User as TimeTrackingUser;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;

class SummaryTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ResponseHelper, ProjectHelper, TimeTrackerTrait;

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
     * @var Collection
     */
    protected $users;

    /**
     * @var Collection
     */
    protected $tracking_users;

    /**
     * @var Carbon
     */
    protected $now;

    /**
     * @var Project
     */
    protected $project;

    /**
     * @var Ticket
     */
    protected $ticket;

    /**
     * @var array
     */
    protected $tracking_activities;

    /**
     * @var Collection
     */
    protected $expected_response_activities;

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

        $this->users = factory(User::class, 5)->create();
        $this->tracking_users = factory(TimeTrackingUser::class, 5)->create(['user_id' => null]);

        $this->tracking_users[2]->external_user_id = 'XYZ_EXTERNAL';
        $this->tracking_users[2]->save();

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $this->ticket = factory(Ticket::class)->create();

        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);

        $this->tracking_activities = $this->createTimeTrackingActivities();
        $this->expected_response_activities = $this->getExpectedResponses();
    }

    /** @test */
    public function it_gets_summary_of_all_company_activities_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id)
            ->seeStatusCode(200);

        $this->verifyResponseSummary([0, 1, 2, 3]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_user_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&user_id=' . $this->user->id)
            ->seeStatusCode(200);

        $this->verifyResponseSummary([0, 3]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_not_ssigned_to_any_user_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&user_id=empty')
            ->seeStatusCode(200);

        $this->verifyResponseSummary([1, 2]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_project_id_when_company_admin_and_project_owner()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);
        $this->setProjectRole($this->project, RoleType::OWNER);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&project_id=' . $this->project->id)
            ->seeStatusCode(200);

        $this->verifyResponseSummary([1, 3]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_not_assigned_to_any_project_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&project_id=empty')
            ->seeStatusCode(200);

        $this->verifyResponseSummary([0, 2]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_ticket_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&ticket_id=' . $this->ticket->id)
            ->seeStatusCode(200);

        $this->verifyResponseSummary([2]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_not_assigned_to_any_ticket_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&ticket_id=empty')
            ->seeStatusCode(200);

        $this->verifyResponseSummary([0, 1, 3]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_external_user_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&external_user_id=XYZ_EXTERNAL')
            ->seeStatusCode(200);

        $this->verifyResponseSummary([2]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_min_utc_started_at_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&min_utc_started_at=' .
            $this->now->subDays(9)->toDateTimeString())
            ->seeStatusCode(200);

        $this->verifyResponseSummary([1, 2, 3]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_min_utc_started_at_and_max_utc_started_at_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id .
            '&min_utc_started_at=' . ((clone $this->now)->subDays(9)->toDateTimeString()) .
            '&max_utc_started_at=' . ((clone $this->now)->subDays(8)->toDateTimeString()))
            ->seeStatusCode(200);

        $this->verifyResponseSummary([1, 2]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_min_utc_finished_at_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&min_utc_finished_at=' .
            $this->now->subDays(3)->toDateTimeString())
            ->seeStatusCode(200);

        $this->verifyResponseSummary([0, 2, 3]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_min_utc_finished_at_and_max_utc_finished_at_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id .
            '&min_utc_finished_at=' . ((clone $this->now)->subDays(3)->toDateTimeString()) .
            '&max_utc_finished_at=' . ((clone $this->now)->subDays(3)->toDateTimeString()))
            ->seeStatusCode(200);

        $this->verifyResponseSummary([0, 2]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_min_tracked_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&min_tracked=500')
            ->seeStatusCode(200);

        $this->verifyResponseSummary([1, 2, 3]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_min_tracked_and_max_tracked_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&min_tracked=500&max_tracked=500')
            ->seeStatusCode(200);

        $this->verifyResponseSummary([2]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_min_activity_level_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&min_activity_level=40')
            ->seeStatusCode(200);

        $this->verifyResponseSummary([1, 2, 3]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_max_activity_level_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&max_activity_level=40')
            ->seeStatusCode(200);

        $this->verifyResponseSummary([0, 2]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_comment_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&comment=ABC')
            ->seeStatusCode(200);

        $this->verifyResponseSummary([0, 2]);
    }

    /** @test */
    public function it_gets_summary_of_company_activities_filtered_by_time_tracking_note_content_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&time_tracking_note_content=WWW')
            ->seeStatusCode(200);

        $this->verifyResponseSummary([3]);
    }

    /** @test */
    public function it_gets_summary_of_own_company_activities_when_company_developer()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id)
            ->seeStatusCode(200);

        $this->verifyResponseSummary([0, 3]);
    }

    /** @test */
    public function it_gets_summary_of_own_project_activities_when_project_selected_and_company_developer()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&project_id=' . $this->project->id)
            ->seeStatusCode(200);

        $this->verifyResponseSummary([3]);
    }

    /** @test */
    public function it_gets_summary_of_all_project_activities_when_project_selected_and_company_developer_but_admin_in_project()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);
        $this->setProjectRole($this->project, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&project_id=' . $this->project->id)
            ->seeStatusCode(200);

        $this->verifyResponseSummary([1, 3]);
    }

    /** @test */
    public function it_gets_summary_of_own_project_activities_when_project_selected_and_company_admin_but_not_project_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id . '&project_id=' . $this->project->id)
            ->seeStatusCode(200);

        $this->verifyResponseSummary([3]);
    }

    /** @test */
    public function it_gets_no_permission_for_client()
    {
        $this->verifyNoPermissionForRole(RoleType::CLIENT);
    }

    /** @test */
    public function it_gets_no_permission_for_dealer()
    {
        $this->verifyNoPermissionForRole(RoleType::DEALER);
    }

    protected function verifyResponseSummary(array $expected_activities_ids)
    {
        $response = $this->decodeResponseJson()['data'];

        $sum_time = 0;
        foreach ($this->expected_response_activities as $key => $activity) {
            if (in_array($key, $expected_activities_ids)) {
                $sum_time += $activity['tracked'];
            }
        }
        $this->assertEquals($sum_time, $response['sum_time']);
    }

    protected function verifyNoPermissionForRole($role_slug)
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, $role_slug);

        $this->get('/integrations/time_tracking/activities/summary/' . '?selected_company_id=' .
            $this->company->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    protected function getExpectedResponses()
    {
        $responses = collect();
        foreach ($this->tracking_activities as $activity) {
            $responses->push($this->getTimeTrackingResponse($activity));
        }

        return $responses;
    }

    protected function getTimeTrackingResponse(Activity $activity)
    {
        $data = $activity->attributesToArray();
        $data['locked'] = $activity->locked_user_id ? true : false;
        $data['activity_level'] =
            $activity->tracked ? (int) (round(100.0 * $activity->activity /
                $activity->tracked)) : 0;
        $data['user']['data'] =
            $activity->user ? $this->getExpectedUserResponse($activity->user) : null;
        $data['project']['data'] = $activity->project ? $activity->project->toArray() : null;
        $data['screens'] = $this->getScreens($activity);
        $data['ticket']['data'] = $activity->ticket ? array_only(
            $activity->ticket->toArray(),
            ['id', 'name', 'title', 'estimate_time']
        ) : null;
        $data['time_tracking_user']['data'] =
            $activity->timeTrackingUser ? $activity->timeTrackingUser->toArray() : null;
        $data['time_tracking_note']['data'] =
            $activity->timeTrackingNote ? $activity->timeTrackingNote->toArray() : null;
        $data['deleted_at'] = null;

        return $data;
    }

    private function getScreens(Activity $activity)
    {
        if (! $activity->relationLoaded('screens')) {
            return [];
        }

        return $activity->getRelation('screens')
            ->map(function (ActivityFrameScreen $activity_frame_screen) {
                return [
                    'url_link' => env('APP_WELCOME_ABSOLUTE_URL') .
                        $activity_frame_screen->screen->url_link,
                    'thumbnail_link' => env('APP_WELCOME_ABSOLUTE_URL') .
                        $activity_frame_screen->screen->thumbnail_link,
                ];
            })->all();
    }
}
