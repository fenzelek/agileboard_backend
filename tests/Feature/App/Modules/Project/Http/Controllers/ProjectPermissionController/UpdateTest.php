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

class UpdateTest extends TestCase
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
     * @scenario Project Permission Updating
     *      @suit Project Permission Updating
     *      @case Success
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::update;
     * @test
     */
    public function update_success()
    {
        $data = $this->getData();

        $response = $this->put($this->getUrl(), $data);
        $response->assertStatus(200);
        $response->assertJsonStructure($this->getResponseStructure());
        $this->project->permission = $this->project->permission->fresh();

        // check tickets permissions
        $this->assertEquals(
            $data['ticket_create'],
            $this->project->permission->ticket_create
        );
        $this->assertEquals(
            $data['ticket_update'],
            $this->project->permission->ticket_update
        );
        $this->assertEquals(
            $data['ticket_destroy'],
            $this->project->permission->ticket_destroy
        );

        // check ticket comments permissions
        $this->assertEquals(
            $data['ticket_comment_create'],
            $this->project->permission->ticket_comment_create
        );
        $this->assertEquals(
            $data['ticket_comment_update'],
            $this->project->permission->ticket_comment_update
        );
        $this->assertEquals(
            $data['ticket_comment_destroy'],
            $this->project->permission->ticket_comment_destroy
        );

        // check ticket visibility permissions
        $this->assertEquals(
            $data['owner_ticket_show'],
            $this->project->permission->owner_ticket_show
        );
        $this->assertEquals(
            $data['admin_ticket_show'],
            $this->project->permission->admin_ticket_show
        );
        $this->assertEquals(
            $data['developer_ticket_show'],
            $this->project->permission->developer_ticket_show
        );
        $this->assertEquals(
            $data['client_ticket_show'],
            $this->project->permission->client_ticket_show
        );
    }

    /**
     * @scenario Project Permission Updating
     *      @suit Project Permission Updating
     *      @case Return 422 when format of data is invalid
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::update;
     * @test
     */
    public function update_return_422_when_format_of_data_is_invalid()
    {
        $data = $this->getData();
        $data['ticket_update'] = [
            'roles' => ['admin'],
        ];

        $response = $this->put($this->getUrl(), $data);
        $fields = $response->decodeResponseJson()['fields'];
        $response->assertStatus(422);
        $this->assertArrayHasKey('ticket_update.relations', $fields);
        $this->assertArrayHasKey('ticket_update.roles.0.name', $fields);
        $this->assertArrayHasKey('ticket_update.roles.0.value', $fields);
    }

    /**
     * @scenario Project Permission Updating
     *      @suit Project Permission Updating
     *      @case Return 401 when user is not company admin and project developer
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::update;
     * @test
     */
    public function update_it_doesnt_allow_to_update_if_user_is_not_company_admin_and_project_developer()
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

        $this->put($this->getUrl())
            ->assertStatus(401);
    }

    /**
     * @scenario Project Permission Updating
     *      @suit Project Permission Updating
     *      @case Allow to update when user is company admin and project developer
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::update;
     * @test
     */
    public function update_it_allows_to_update_if_user_is_company_admin_and_project_developer()
    {
        $data = $this->getData();

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

        $this->put($this->getUrl(), $data)->assertStatus(200);
    }

    /**
     * @scenario Project Permission Updating
     *      @suit Project Permission Updating
     *      @case Return 401 when user is company developer and project admin
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::update;
     * @test
     */
    public function update_it_doesnt_allow_to_update_if_user_is_company_developer_and_project_admin()
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

        $this->put($this->getUrl())->assertStatus(401);
    }

    /**
     * @scenario Project Permission Updating
     *      @suit Project Permission Updating
     *      @case Return 404 when updating wrong project
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::update;
     * @test
     */
    public function update_wrong_project_id_should_throw_404_exception()
    {
        $this->put('/projects/' . ((int) $this->project->id + 1) . '/permissions' .
            '?selected_company_id=' . $this->company->id)->assertStatus(404);
    }

    /**
     * @scenario Project Permission Updating
     *      @suit Project Permission Updating
     *      @case Return 401 when sending wrong company id
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::update;
     * @test
     */
    public function update_wrong_company_id_should_throw_401_exception()
    {
        $this->put('/projects/' . $this->project->id . '/permissions' .
            '?selected_company_id=' . ((int) $this->company->id + 1))->assertStatus(401);
    }

    /**
     * @scenario Project Permission Updating
     *      @suit Project Permission Updating
     *      @case Return 404 when updating already deleted project
     *
     * @covers \App\Modules\Project\Http\Controllers\ProjectPermissionController::update;
     * @test
     */
    public function update_already_deleted_project_should_throw_404_exception()
    {
        $delete_time = Carbon::parse('2017-01-01 12:00:00')->toDateTimeString();
        $this->project->deleted_at = $delete_time;
        $this->project->save();
        $this->assertSame($delete_time, $this->project->fresh()->deleted_at->toDateTimeString());

        $this->put($this->getUrl())->assertStatus(404);
    }

    /**
     * @return string
     */
    private function getUrl()
    {
        return route('project-permissions.update', [
            $this->project->id,
            'selected_company_id' => $this->company->id,
        ]);
    }

    private function getData()
    {
        return [
            'ticket_create' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                    ['name' => 'reporter', 'value' => true],
                ],
            ],
            'ticket_update' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                    ['name' => 'reporter', 'value' => true],
                ],
            ],
            'ticket_destroy' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                    ['name' => 'reporter', 'value' => true],
                ],
            ],
            'ticket_comment_create' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                    ['name' => 'reporter', 'value' => true],
                ],
            ],
            'ticket_comment_update' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                    ['name' => 'reporter', 'value' => true],
                ],
            ],
            'ticket_comment_destroy' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                ],
            ],
            'owner_ticket_show' => [
                ['name' => 'assigned', 'value' => true],
            ],
            'admin_ticket_show' => [
                ['name' => 'reporter', 'value' => true],
            ],
            'developer_ticket_show' => [
                ['name' => 'not_assigned', 'value' => true],
            ],
            'client_ticket_show' => [
                ['name' => 'all', 'value' => true],
            ],
        ];
    }
}
