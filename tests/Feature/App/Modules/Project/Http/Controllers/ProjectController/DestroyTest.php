<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController;

use App\Models\Db\Package;
use App\Models\Db\Role;
use App\Models\Db\UserCompany;
use App\Models\Other\UserCompanyStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\Project;
use App\Helpers\ErrorCode;
use Carbon\Carbon;
use Tests\BrowserKitTestCase;

class DestroyTest extends BrowserKitTestCase
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
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_FREE);

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $this->project->users()
            ->attach($this->user, ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
    }

    /** @test */
    public function destroy_it_deletes_project_with_success()
    {
        $this->assertSame(0, Project::onlyTrashed()->count());
        $this->delete('/projects/' . $this->project->id . '?selected_company_id=' .
            $this->company->id)->seeStatusCode(204);

        $this->assertSame(1, Project::onlyTrashed()->count());
        $this->assertSame(
            $this->now->toDateTimeString(),
            $this->project->fresh()->deleted_at->toDateTimeString()
        );
    }

    /** @test */
    public function destroy_it_doesnt_allow_to_delete_if_user_is_not_company_admin_and_project_developer()
    {
        // company role
        UserCompany::whereRaw('1 = 1')->delete();
        $user_company = new UserCompany();
        $user_company->user_id = $this->user->id;
        $user_company->company_id = $this->company->id;
        $user_company->role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $user_company->status = UserCompanyStatus::APPROVED;
        $user_company->save();

        // project role
        $this->project->users()->sync([$this->user->id => ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]]);

        $this->assertSame(0, Project::onlyTrashed()->count());
        $this->delete('/projects/' . $this->project->id . '?selected_company_id=' .
            $this->company->id)->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);

        $this->assertSame(0, Project::onlyTrashed()->count());
    }

    /** @test */
    public function destroy_it_allows_to_delete_if_user_is_company_admin_and_project_developer()
    {
        // company role
        UserCompany::whereRaw('1 = 1')->delete();
        $user_company = new UserCompany();
        $user_company->user_id = $this->user->id;
        $user_company->company_id = $this->company->id;
        $user_company->role_id = Role::findByName(RoleType::ADMIN)->id;
        $user_company->status = UserCompanyStatus::APPROVED;
        $user_company->save();

        // project role
        $this->project->users()->sync([$this->user->id => ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]]);

        $this->assertSame(0, Project::onlyTrashed()->count());
        $this->delete('/projects/' . $this->project->id . '?selected_company_id=' .
            $this->company->id)->seeStatusCode(204);

        $this->assertSame(1, Project::onlyTrashed()->count());
        $this->assertSame(
            $this->now->toDateTimeString(),
            $this->project->fresh()->deleted_at->toDateTimeString()
        );
    }

    /** @test */
    public function destroy_it_doesnt_allow_to_delete_if_user_is_company_developer_and_project_admin()
    {
        // company role
        UserCompany::whereRaw('1 = 1')->delete();
        $user_company = new UserCompany();
        $user_company->user_id = $this->user->id;
        $user_company->company_id = $this->company->id;
        $user_company->role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $user_company->status = UserCompanyStatus::APPROVED;
        $user_company->save();

        // project role
        $this->project->users()->sync([$this->user->id => ['role_id' => Role::findByName(RoleType::ADMIN)->id]]);

        $this->assertSame(0, Project::onlyTrashed()->count());
        $this->delete('/projects/' . $this->project->id . '?selected_company_id=' .
            $this->company->id)->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);

        $this->assertSame(0, Project::onlyTrashed()->count());
    }

    /** @test */
    public function destroy_wrong_project_id_should_throw_404_exception()
    {
        $this->assertSame(0, Project::onlyTrashed()->count());
        $this->delete('/projects/' . ((int) $this->project->id + 1) . '?selected_company_id=' .
            $this->company->id)->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function destroy_wrong_company_id_should_throw_401_exception()
    {
        $this->assertSame(0, Project::onlyTrashed()->count());
        $this->delete('/projects/' . $this->project->id . '?selected_company_id=' .
            ((int) $this->company->id + 1))->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function destroy_deleting_already_deleted_project_should_throw_404_exception()
    {
        $delete_time = Carbon::parse('2017-01-01 12:00:00')->toDateTimeString();
        $this->project->deleted_at = $delete_time;
        $this->project->save();
        $this->assertSame($delete_time, $this->project->fresh()->deleted_at->toDateTimeString());

        $this->delete('/projects/' . $this->project->id . '?selected_company_id=' .
            $this->company->id)->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);

        $this->assertSame($delete_time, $this->project->fresh()->deleted_at->toDateTimeString());
    }
}
