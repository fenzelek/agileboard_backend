<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController;

use App\Models\Db\Package;
use App\Models\Db\Role;
use App\Models\Other\ModuleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\Project;
use Carbon\Carbon;
use Tests\BrowserKitTestCase;

class DestroyWithBlockadedCompanyTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    protected $company;

    /**
     * @var Project
     */
    protected $project;
    protected $now;

    public function setUp():void
    {
        parent::setUp();

        config()->set('app_settings.package_portal_name', 'ab');

        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_FREE);

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $this->project->users()
            ->attach($this->user, ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
    }

    /** @test */
    public function destroy_in_blockaded_company_and_unlocked_company()
    {
        $this->company->blockade_company = ModuleType::PROJECTS_MULTIPLE_PROJECTS;
        $this->company->save();

        $this->assertSame(0, Project::onlyTrashed()->count());
        $this->delete('/projects/' . $this->project->id . '?selected_company_id=' .
            $this->company->id)->seeStatusCode(204);

        $this->assertSame(1, Project::onlyTrashed()->count());
        $this->assertSame(
            $this->now->toDateTimeString(),
            $this->project->fresh()->deleted_at->toDateTimeString()
        );

        $this->assertNull($this->company->fresh()->blockade_company);
    }

    /** @test */
    public function destroy_in_blockaded_company_with_more_module_blockaded()
    {
        $this->company->blockade_company = implode(',', [ModuleType::PROJECTS_MULTIPLE_PROJECTS, ModuleType::GENERAL_MULTIPLE_USERS]);
        $this->company->save();

        $this->assertSame(0, Project::onlyTrashed()->count());
        $this->delete('/projects/' . $this->project->id . '?selected_company_id=' .
            $this->company->id)->seeStatusCode(204);

        $this->assertSame(1, Project::onlyTrashed()->count());
        $this->assertSame(
            $this->now->toDateTimeString(),
            $this->project->fresh()->deleted_at->toDateTimeString()
        );

        $this->assertSame(ModuleType::GENERAL_MULTIPLE_USERS, $this->company->fresh()->blockade_company);
    }

    /** @test */
    public function destroy_in_blockaded_company_too_many_projects()
    {
        factory(Project::class, 10)->create(['company_id' => $this->company->id]);

        $this->company->blockade_company = implode(',', [ModuleType::PROJECTS_MULTIPLE_PROJECTS, ModuleType::GENERAL_MULTIPLE_USERS]);
        $this->company->save();

        $this->assertSame(0, Project::onlyTrashed()->count());
        $this->delete('/projects/' . $this->project->id . '?selected_company_id=' .
            $this->company->id)->seeStatusCode(204);

        $this->assertSame(1, Project::onlyTrashed()->count());
        $this->assertSame(
            $this->now->toDateTimeString(),
            $this->project->fresh()->deleted_at->toDateTimeString()
        );

        $this->assertSame($this->company->blockade_company, $this->company->fresh()->blockade_company);
    }
}
