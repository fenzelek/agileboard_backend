<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingProjectController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Integration\TimeTracking\Note;
use App\Models\Db\Integration\TimeTracking\Project as TimeTrackingProject;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;

class UpdateTest extends BrowserKitTestCase
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
     * @var Collection
     */
    protected $projects;

    /**
     * @var TimeTrackingProject
     */
    protected $tracking_project;

    /**
     * @var Collection
     */
    protected $tickets;

    /**
     * @var Collection
     */
    protected $notes;

    /**
     * @var Collection
     */
    protected $activities;

    /**
     * @var Carbon
     */
    protected $now;

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

        $this->projects = factory(Project::class, 2)->create([
            'company_id' => $this->company->id,
        ]);
        $this->projects[0]->short_name = 'SAMPLE';
        $this->projects[0]->save();

        $this->tickets = $this->createTickets();

        $this->tracking_project = $this->createTimeTrackingProject();
        $this->notes = $this->createNotes();

        $this->activities = $this->createActivities();

        $this->now = Carbon::now()->addDays(10);
    }

    /** @test */
    public function it_updates_project_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        Carbon::setTestNow($this->now);

        $current_project = $this->tracking_project->fresh();
        $this->assertNull($current_project->project_id);

        $this->put('/integrations/time_tracking/projects/' . $current_project->id .
            '?selected_company_id=' .
            $this->company->id, ['project_id' => $this->projects[0]->id])
            ->seeStatusCode(200);

        $modified_project = $current_project->fresh();

        $this->assertNotNull($modified_project->project_id);

        $expected_result = array_except(
            $current_project->attributesToArray(),
            ['project_id', 'updated_at']
        ) +
            [
                'project_id' => $this->projects[0]->id,
                'updated_at' => $this->now->toDateTimeString(),
            ];

        // verify record
        $this->assertEquals($expected_result, $modified_project->attributesToArray());

        // verify response
        $this->assertEquals($expected_result, $this->decodeResponseJson()['data']);

        // verify whether all activities were updated - project should be assigned and for some of
        // them also ticket should be assigned
        $expected_activities = $this->expectedActivities();

        foreach ($expected_activities as $index => $expected_activity) {
            $db_activity = Activity::findOrFail($expected_activity['id']);
            $this->assertEquals(
                $expected_activity,
                $db_activity->attributesToArray(),
                'Activity with index=' . $index . ' has valid data'
            );
        }
    }

    /** @test */
    public function it_gets_error_when_trying_to_update_other_company_project()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::OWNER);

        $other_company = factory(Company::class)->create();

        $hubstaff_integration = $other_company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
        ]);

        $time_tracking_project = factory(TimeTrackingProject::class)->create([
            'integration_id' => $hubstaff_integration->id,
            'project_id' => null,
            'external_project_name' => 'test very_specific_project abc',
        ]);

        $this->put('/integrations/time_tracking/projects/' . $time_tracking_project->id .
            '?selected_company_id=' .
            $this->company->id, ['project_id' => $this->projects[0]->id]);

        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function it_gets_validation_error_when_trying_to_set_other_company_project()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::OWNER);

        $other_company = factory(Company::class)->create();
        $other_project = factory(Project::class)->create(['company_id' => $other_company->id]);

        $current_project = $this->tracking_project->fresh();

        $this->put('/integrations/time_tracking/projects/' . $current_project->id .
            '?selected_company_id=' .
            $this->company->id, ['project_id' => $other_project->id]);

        $this->verifyValidationResponse(['project_id']);
    }

    /** @test */
    public function it_gets_no_permission_for_developer()
    {
        $this->verifyNoPermissionForRole(RoleType::DEVELOPER);
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

    protected function verifyNoPermissionForRole($role_slug)
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, $role_slug);

        $current_project = $this->tracking_project->fresh();
        $this->assertNull($current_project->project_id);

        $this->put('/integrations/time_tracking/projects/' . $current_project->id .
            '?selected_company_id=' .
            $this->company->id, ['project_id' => $this->projects[0]->id]);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    protected function createTimeTrackingProject()
    {
        return factory(TimeTrackingProject::class)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'project_id' => null,
            'external_project_name' => 'test very_specific_project abc',
        ]);
    }

    protected function createTickets()
    {
        $tickets = collect();
        $tickets->push(factory(Ticket::class)->create([
            'project_id' => $this->projects[0]->id,
            'title' => 'SAMPLE-1',
        ]));

        $tickets->push(factory(Ticket::class)->create([
            'project_id' => $this->projects[1]->id,
            'title' => 'SAMPLE-2',
        ]));

        $tickets->push(factory(Ticket::class)->create([
            'project_id' => $this->projects[0]->id,
            'title' => 'SAMPLE-3',
        ]));

        $tickets->push(factory(Ticket::class)->create([
            'project_id' => $this->projects[1]->id,
            'title' => 'SAMPLE-4',
        ]));

        return $tickets;
    }

    protected function createNotes()
    {
        $notes = collect();

        $notes->push(factory(Note::class)->create([
            'content' => $this->tickets[0]->title,
        ]));

        $notes->push(factory(Note::class)->create([
            'content' => $this->tickets[1]->title,
        ]));

        $notes->push(factory(Note::class)->create([
            'content' => $this->tickets[2]->title,
        ]));

        $notes->push(factory(Note::class)->create([
            'content' => $this->tickets[3]->title,
        ]));

        return $notes;
    }

    protected function createActivities()
    {
        $activities = collect();

        $other_time_tracking_project = factory(TimeTrackingProject::class)->create();

        $activities->push(factory(Activity::class)->create([
            'ticket_id' => null,
            'project_id' => null,
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
            'locked_user_id' => 500,
        ]));

        $activities->push(factory(Activity::class)->create([
            'ticket_id' => null,
            'project_id' => null,
            'time_tracking_project_id' => $this->tracking_project->id,
            'time_tracking_note_id' => $this->notes[0]->id,
            'locked_user_id' => null,
        ]));

        $activities->push(factory(Activity::class)->create([
            'ticket_id' => 789,
            'project_id' => null,
            'time_tracking_project_id' => $this->tracking_project->id,
            'time_tracking_note_id' => null,
            'locked_user_id' => 300,
        ]));

        $activities->push(factory(Activity::class)->create([
            'ticket_id' => null,
            'project_id' => null,
            'time_tracking_project_id' => $other_time_tracking_project->id,
            'time_tracking_note_id' => $this->notes[1]->id,
            'locked_user_id' => 200,
        ]));

        $activities->push(factory(Activity::class)->create([
            'ticket_id' => null,
            'project_id' => null,
            'time_tracking_project_id' => $this->tracking_project->id,
            'time_tracking_note_id' => $this->notes[2]->id,
            'locked_user_id' => 100,
        ]));

        $activities->push(factory(Activity::class)->create([
            'ticket_id' => null,
            'project_id' => null,
            'time_tracking_project_id' => $other_time_tracking_project->id,
            'time_tracking_note_id' => $this->notes[3]->id,
            'locked_user_id' => 700,
        ]));

        return $activities;
    }

    protected function expectedActivities()
    {
        $activities = collect();

        $activities->push($this->activities[0]->fresh()->attributesToArray());

        $activities->push(array_except(
            $this->activities[1]->fresh()->attributesToArray(),
            ['updated_at', 'project_id', 'ticket_id']
        ) + [
                'updated_at' => $this->now->toDateTimeString(),
                'project_id' => $this->projects[0]->id,
                'ticket_id' => $this->tickets[0]->id,
            ]);

        $activities->push(array_except(
            $this->activities[2]->fresh()->attributesToArray(),
            ['updated_at', 'project_id', 'ticket_id']
        ) + [
                'updated_at' => $this->now->toDateTimeString(),
                'project_id' => $this->projects[0]->id,
                'ticket_id' => null,
            ]);

        $activities->push($this->activities[3]->fresh()->attributesToArray());

        $activities->push(array_except(
            $this->activities[4]->fresh()->attributesToArray(),
            ['updated_at', 'project_id', 'ticket_id']
        ) + [
                'updated_at' => $this->now->toDateTimeString(),
                'project_id' => $this->projects[0]->id,
                'ticket_id' => $this->tickets[2]->id,
            ]);

        $activities->push($this->activities[5]->fresh()->attributesToArray());

        return $activities;
    }
}
