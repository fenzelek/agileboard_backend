<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\Project;
use App\Helpers\ErrorCode;
use Carbon\Carbon;
use Tests\BrowserKitTestCase;

class CloseTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    protected $company;
    protected $project;
    protected $now;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $this->project->users()->attach($this->user);
    }

    /** @test */
    public function close_it_closes_project_with_success()
    {
        $this->assertNull($this->project->fresh()->closed_at);
        $this->put('/projects/' . $this->project->id . '/close?selected_company_id=' .
            $this->company->id, [
            'status' => 'close',
        ])->seeStatusCode(200)->isJson();

        $this->assertSame($this->now->toDateTimeString(), $this->project->fresh()->closed_at->toDateTimeString());
    }

    /** @test */
    public function close_it_closes_project_and_try_to_open_with_post_with_success()
    {
        $this->project->closed_at = Carbon::now()->toDateTimeString();
        $this->project->save();
        $this->assertNotNull($this->project->fresh()->closed_at);

        $this->put('/projects/' . $this->project->id . '/close?selected_company_id=' .
            $this->company->id, [
            'status' => 'open',
        ])->seeStatusCode(200)->isJson();

        $this->assertNull($this->project->fresh()->closed_at);
    }

    /** @test */
    public function close_closing_project_with_client_role_should_throw_401_exception()
    {
        $this->company = $this->createCompanyWithRole(RoleType::CLIENT);

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $this->project->users()->attach($this->user);
        $this->assertNull($this->project->fresh()->closed_at);

        $this->put('/projects/' . $this->project->id . '/close?selected_company_id=' .
            $this->company->id, [
            'status' => 'close',
        ])->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function close_wrong_project_id_should_throw_404_exception()
    {
        $this->put('/projects/' . ((int) $this->project->id + 1) . '/close?selected_company_id=' .
            $this->company->id, [
            'status' => 'close',
        ])->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function close_wrong_company_id_should_throw_401_exception()
    {
        $this->put('/projects/' . $this->project->id . '/close?selected_company_id=' .
            ((int) $this->company->id + 1), [
            'status' => 'close',
        ])->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function close_empty_status_should_throw_validation_error()
    {
        $this->assertNull($this->project->fresh()->closed_at);
        $this->put('/projects/' . $this->project->id . '/close?selected_company_id=' .
            $this->company->id, [
            'status' => '',
        ])->verifyValidationResponse(['status']);
    }

    /** @test */
    public function close_closing_already_closed_project_returns_project_data()
    {
        $closing_time = Carbon::parse('2017-01-01 12:00:00')->toDateTimeString();
        $this->project->closed_at = $closing_time;
        $this->project->save();
        $this->assertNotNull($this->project->fresh()->closed_at);

        $this->put('/projects/' . $this->project->id . '/close?selected_company_id=' .
            $this->company->id, [
            'status' => 'close',
        ])->seeStatusCode(200)->isJson();

        $this->assertSame($closing_time, $this->project->fresh()->closed_at->toDateTimeString());
    }

    /** @test */
    public function close_opening_already_opened_project_returns_project_data()
    {
        $this->assertNull($this->project->fresh()->closed_at);
        $this->put('/projects/' . $this->project->id . '/close?selected_company_id=' .
            $this->company->id, [
            'status' => 'open',
        ])->seeStatusCode(200)->isJson();

        $this->assertNull($this->project->fresh()->closed_at);
    }
}
