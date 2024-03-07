<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectPermissionController;

use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Models\Db\Role;
use App\Models\Db\UserCompany;
use App\Models\Other\UserCompanyStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\Project;
use Carbon\Carbon;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use DatabaseTransactions, TestTrait;

    /** @var Company  */
    protected $company;
    /** @var Project  */
    protected $project;
    protected $now;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRoleAndPackage(
            RoleType::ADMIN,
            Package::CEP_FREE
        );

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $this->project->users()
            ->attach($this->user, ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
    }

    /**
     * @scenario Show a project permissions
     *      @suit Show a project permissions
     *      @case Success
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::show;
     * @test
     */
    public function show_success()
    {
        $response = $this->get($this->getUrl());
        $response->assertStatus(200);
        $response->assertJsonStructure($this->getResponseStructure());
    }

    /**
     * @scenario Show a project permissions
     *      @suit Show a project permissions
     *      @case Return 401 when user is not company admin and project developer
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::show;
     * @test
     */
    public function update_it_doesnt_allow_to_show_if_user_is_not_company_admin_and_project_developer()
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
        $this->project->users()->sync([$this->user->id => [
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
        ]]);

        $this->get($this->getUrl())
            ->assertStatus(401);
    }

    /**
     * @scenario Show a project permissions
     *      @suit Show a project permissions
     *      @case Allow to show permissions when user is company admin and project developer
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::show;
     * @test
     */
    public function show_it_allows_to_show_if_user_is_company_admin_and_project_developer()
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
        $this->project->users()->sync([$this->user->id => [
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
        ]]);

        $this->get($this->getUrl())->assertStatus(200);
    }

    /**
     * @scenario Show a project permissions
     *      @suit Show a project permissions
     *      @case Return 401 when user is company developer and project admin
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::show;
     * @test
     */
    public function show_it_doesnt_allow_to_show_if_user_is_company_developer_and_project_admin()
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
        $this->project->users()->sync([$this->user->id => [
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
        ]]);

        $this->get($this->getUrl())->assertStatus(401);
    }

    /**
     * @scenario Show a project permissions
     *      @suit Show a project permissions
     *      @case Return 404 when updating wrong project
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::show;
     * @test
     */
    public function show_wrong_project_id_should_throw_404_exception()
    {
        $this->get('/projects/' . ((int) $this->project->id + 1) . '/permissions' .
            '?selected_company_id=' . $this->company->id)->assertStatus(404);
    }

    /**
     * @scenario Show a project permissions
     *      @suit Show a project permissions
     *      @case Return 401 when sending wrong company id
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::show;
     * @test
     */
    public function show_wrong_company_id_should_throw_401_exception()
    {
        $this->get('/projects/' . $this->project->id . '/permissions' .
            '?selected_company_id=' . ((int) $this->company->id + 1))->assertStatus(401);
    }

    /**
     * @scenario Show a project permissions
     *      @suit Show a project permissions
     *      @case Return 404 when updating already deleted project
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::show;
     * @test
     */
    public function show_already_deleted_project_should_throw_404_exception()
    {
        $delete_time = Carbon::parse('2017-01-01 12:00:00')->toDateTimeString();
        $this->project->deleted_at = $delete_time;
        $this->project->save();
        $this->assertSame($delete_time, $this->project->fresh()->deleted_at->toDateTimeString());

        $this->get($this->getUrl())->assertStatus(404);
    }

    /**
     * @return string
     */
    private function getUrl()
    {
        return route('project-permissions.show', [
            $this->project->id,
            'selected_company_id' => $this->company->id,
        ]);
    }
}
