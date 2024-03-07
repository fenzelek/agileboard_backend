<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\ProjectUser;
use App\Models\Db\Package;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Other\ModuleType;
use App\Models\Other\RoleType;
use App\Models\Db\UserCompany;
use App\Models\Other\UserCompanyStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class UserControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /** @test */
    public function index_user_list_structure_response()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/companies/current/users?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'email',
                        'first_name',
                        'last_name',
                        'company_role_id',
                        'company_status',
                        'company_title',
                        'company_skills',
                        'company_description',
                    ],
                ],
            ])->isJson();
    }

    /** @test */
    public function index_user_list_check_correct_data()
    {
        $this->createUser();
        $companies = factory(Company::class, 2)->create();

        $users_company = [
            $this->add_user_to_company(
                $companies[0],
                RoleType::OWNER,
                UserCompanyStatus::APPROVED,
                $this->user->id
            ),
            $this->add_user_to_company($companies[0]),
            $this->add_user_to_company($companies[0], RoleType::DEVELOPER, UserCompanyStatus::SUSPENDED),
        ];

        $user_company_other = $this->add_user_to_company($companies[1]);
        auth()->loginUsingId($this->user->id);

        $this->get('/companies/current/users?selected_company_id=' . $companies[0]->id)
            ->assertResponseOk();
        $json = $this->decodeResponseJson();

        $this->assertEquals(count($users_company), count($json['data']));

        $users_company_response = [
            $this->userCompanyToArray($users_company[0]),
            $this->userCompanyToArray($users_company[1]),
            $this->userCompanyToArray($users_company[2]),
        ];

        $this->assertEquals($users_company_response, $json['data']);
        $this->assertNotContains($this->userCompanyToArray($user_company_other), $json['data']);
    }

    /** @test */
    public function index_user_list_filter_by_status_data()
    {
        $this->createUser();
        $company = factory(Company::class)->create();
        $users_company = [
            $this->add_user_to_company(
                $company,
                RoleType::OWNER,
                UserCompanyStatus::APPROVED,
                $this->user->id
            ),
            $this->add_user_to_company($company),
            $this->add_user_to_company($company, RoleType::DEVELOPER, UserCompanyStatus::SUSPENDED),
        ];

        auth()->loginUsingId($this->user->id);

        $this->get('/companies/current/users?selected_company_id=' . $company->id .
            '&company_status=' . UserCompanyStatus::APPROVED)->assertResponseOk();
        $json = $this->decodeResponseJson();

        $this->assertEquals(2, count($json['data']));

        $users_company_response = [
            $this->userCompanyToArray($users_company[0]),
            $this->userCompanyToArray($users_company[1]),
        ];

        $this->assertEquals($json['data'], $users_company_response);
        $this->assertNotContains($this->userCompanyToArray($users_company[2]), $json['data']);
    }

    /** @test */
    public function index_user_list_allow_to_search()
    {
        $this->createUser();
        $companies = factory(Company::class, 2)->create();

        // set current user first name and last name to not be caught by search
        $this->user->first_name = 'Test';
        $this->user->last_name = 'User';
        $this->user->save();

        $this->add_user_to_company(
            $companies[0],
            RoleType::OWNER,
            UserCompanyStatus::APPROVED,
            $this->user->id
        );

        $users = collect([
            factory(User::class)->create([
                'email' => 'foo@example.com',
                'first_name' => 'Abc',
                'last_name' => 'Def',
            ]),
            factory(User::class)->create([
                'first_name' => 'Mister Foo',
                'last_name' => 'Def',
                'email' => 'ggg@example.com',
            ]),
            factory(User::class)->create([
                'last_name' => 'Fool',
                'first_name' => 'Abc',
                'email' => 'ggg2@example.com',
            ]),
            factory(User::class)->create([
                'email' => 'baz@example.com',
                'first_name' => 'Abc',
                'last_name' => 'Def',
            ]),
            factory(User::class)->create([
                'first_name' => 'Mister Baz',
                'last_name' => 'Def',
                'email' => 'ggg3@example.com',
            ]),
            factory(User::class)->create([
                'last_name' => 'Baz',
                'first_name' => 'Abc',
                'email' => 'ggg4@example.com',
            ]),
            factory(User::class)->create([
                'email' => 'bar@example.com',
                'first_name' => 'Abc',
                'last_name' => 'Def',
            ]),
            factory(User::class)->create([
                'first_name' => 'Mister Bar',
                'last_name' => 'Def',
                'email' => 'ggg5@example.com',
            ]),
            factory(User::class)->create([
                'last_name' => 'Bar',
                'first_name' => 'Abc',
                'email' => 'ggg6@example.com',
            ]),
        ]);
        $companies[0]->users()->attach($users->pluck('id')->all(), [
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
            'status' => UserCompanyStatus::APPROVED,
        ]);

        auth()->loginUsingId($this->user->id);

        $this->get('/companies/current/users?selected_company_id=' . $companies[0]->id .
            '&search=foo bar')
            ->assertResponseOk();

        $response_data = collect($this->response->getData()->data);
        $this->assertCount(6, $response_data);

        // make sure only those containing foo and bar are present and not those with baz
        $response_users = $response_data->pluck('id');
        $this->assertTrue($response_users->contains($users->get(0)->id));
        $this->assertTrue($response_users->contains($users->get(1)->id));
        $this->assertTrue($response_users->contains($users->get(2)->id));
        $this->assertTrue($response_users->contains($users->get(6)->id));
        $this->assertTrue($response_users->contains($users->get(7)->id));
        $this->assertTrue($response_users->contains($users->get(8)->id));
    }

    /** @test */
    public function index_get_wrong_select_company_id()
    {
        $this->createUser();
        $this->createCompanyWithRole(RoleType::OWNER);
        $company_other = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $this->get('/companies/current/users?selected_company_id=' . $company_other->id);
        $json = $this->decodeResponseJson();
        $this->assertSame($json['code'], 'general.no_action_permission');
    }

    /** @test */
    public function update_error_has_not_permission()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);

        $user_company = $this->add_user_to_company($company);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::DEVELOPER),
                Role::findByName(RoleType::ADMIN),
            ]
            ),
            $company
        );

        $data = [
            'user_id' => $user_company->user_id,
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
        ];

        $this->put('/companies/current/users?selected_company_id=' . $company->id, $data);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function update_validation_error()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::DEVELOPER),
                Role::findByName(RoleType::ADMIN),
            ]
            ),
            $company
        );

        $this->put('/companies/current/users?selected_company_id=' . $company->id, []);

        $this->verifyValidationResponse(['user_id', 'role_id']);
    }

    /** @test */
    public function update_error_cant_update_owner()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $user_company = $this->add_user_to_company($company, RoleType::OWNER);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::DEVELOPER),
                Role::findByName(RoleType::ADMIN),
            ]
            ),
            $company
        );

        $data = [
            'user_id' => $user_company->user_id,
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
        ];

        $this->put('/companies/current/users?selected_company_id=' . $company->id, $data);
        $this->verifyValidationResponse(['user_id']);
    }

    /** @test */
    public function update_error_cant_set_as_owner()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $user_company = $this->add_user_to_company($company, RoleType::DEVELOPER);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::DEVELOPER),
                Role::findByName(RoleType::ADMIN),
            ]
            ),
            $company
        );

        $data = [
            'user_id' => $user_company->user_id,
            'role_id' => Role::findByName(RoleType::OWNER)->id,
        ];

        $this->put('/companies/current/users?selected_company_id=' . $company->id, $data);
        $this->verifyValidationResponse(['role_id']);
    }

    /** @test */
    public function update_error_user_not_found()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $other_company = factory(Company::class)->create();

        $user_company = $this->add_user_to_company($other_company, RoleType::DEVELOPER);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::DEVELOPER),
                Role::findByName(RoleType::ADMIN),
            ]
            ),
            $company
        );

        $data = [
            'user_id' => $user_company->user_id,
            'role_id' => Role::findByName(RoleType::OWNER)->id,
        ];

        $this->put('/companies/current/users?selected_company_id=' . $company->id, $data);
        $this->verifyValidationResponse(['user_id']);
    }

    /** @test */
    public function update_success_by_admin()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::DEVELOPER),
                Role::findByName(RoleType::ADMIN),
            ]
            ),
            $company
        );

        $user_company = $this->add_user_to_company($company, RoleType::ADMIN);

        $data = [
            'user_id' => $user_company->user_id,
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
        ];

        $this->put('/companies/current/users?selected_company_id=' . $company->id, $data)->seeStatusCode(200);

        $user_company = UserCompany::where('user_id', $data['user_id'])->where('company_id', $company->id)->first();

        $this->assertSame($data['role_id'], $user_company->role_id);
    }

    /** @test */
    public function update_success_by_owner()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::DEVELOPER),
                Role::findByName(RoleType::ADMIN),
            ]
            ),
            $company
        );

        $user_company = $this->add_user_to_company($company, RoleType::ADMIN);

        $data = [
            'user_id' => $user_company->user_id,
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
        ];

        $this->put('/companies/current/users?selected_company_id=' . $company->id, $data)->seeStatusCode(200);

        $user_company = UserCompany::where('user_id', $data['user_id'])->where('company_id', $company->id)->first();

        $this->assertSame($data['role_id'], $user_company->role_id);
    }

    /** @test */
    public function delete_owner_has_permission()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();

        $this->delete('/companies/current/users/' . $user->id . '?selected_company_id=' .
            $company->id)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function delete_admin_has_permission()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();

        $this->delete('/companies/current/users/' . $user->id . '?selected_company_id=' .
            $company->id)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function delete_regular_user_has_no_permission()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();

        $this->delete('/companies/current/users/' . $user->id . '?selected_company_id=' .
            $company->id)
            ->assertResponseStatus(401);
    }

    /** @test */
    public function delete_soft_stiring_in_database()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_FREE);
        auth()->loginUsingId($this->user->id);

        $regular_users = factory(User::class, 2)->create();
        $this->assignUsersToCompany($regular_users, $company);

        //>project data
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $project->users()->attach($regular_users[0]->id, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $other_company = factory(Company::class)->create();
        $project2 = factory(Project::class)->create(['company_id' => $other_company->id]);
        $project2->users()->attach($regular_users[0]->id, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $project_users_count = ProjectUser::count();
        //<project data

        $company_users_count = User::whereHas('companies', function ($query) use ($company) {
            $query->where('id', $company->id)
                ->where('status', '<>', UserCompanyStatus::DELETED);
        })->count();

        $this->delete('/companies/current/users/' . $regular_users[0]->id .
            '?selected_company_id=' . $company->id)
            ->assertResponseStatus(200);

        $decrese_users_count = User::whereHas('companies', function ($query) use ($company) {
            $query->where('id', $company->id)
                ->where('status', '<>', UserCompanyStatus::DELETED);
        })->count();

        $deleted_company_user = $regular_users[0]
            ->userCompanies()->inCompany($company)->first();
        $no_touch_company_user = $regular_users[1]
            ->userCompanies()->inCompany($company)->first();

        $this->assertSame($company_users_count - 1, $decrese_users_count);
        $this->assertSame(UserCompanyStatus::DELETED, $deleted_company_user->status);
        $this->assertSame(UserCompanyStatus::APPROVED, $no_touch_company_user->status);

        $this->assertSame($project_users_count - 1, ProjectUser::count());
        $this->assertSame(null, ProjectUser::where('user_id', $regular_users[0]->id)->where('project_id', $project->id)->first());

        $this->assertNull($company->fresh()->blockade_company);
    }

    /** @test */
    public function delete_in_blockaded_company_with_unblocking()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $company->blockade_company = ModuleType::GENERAL_MULTIPLE_USERS;
        $company->save();
        auth()->loginUsingId($this->user->id);

        $regular_users = factory(User::class, 2)->create();
        $this->assignUsersToCompany($regular_users, $company);

        //>project data
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $project->users()->attach($regular_users[0]->id, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $other_company = factory(Company::class)->create();
        $project2 = factory(Project::class)->create(['company_id' => $other_company->id]);
        $project2->users()->attach($regular_users[0]->id, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $this->delete('/companies/current/users/' . $regular_users[0]->id .
            '?selected_company_id=' . $company->id)
            ->assertResponseStatus(200);

        $this->assertNull($company->fresh()->blockade_company);
    }

    /** @test */
    public function delete_user_has_no_permission_to_destroy_user_other_company()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $company_other = factory(Company::class)->create();

        $regular_users = factory(User::class, 2)->create();
        $this->assignUsersToCompany($regular_users, $company_other);

        $this->delete('/companies/current/users/' . $regular_users[0]->id .
            '?selected_company_id=' . $company_other->id)
            ->assertResponseStatus(401);
    }

    /** @test */
    public function delete_fail_destroy_user_out_of_company()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $company_other = factory(Company::class)->create();

        $regular_users = factory(User::class, 2)->create();
        $this->assignUsersToCompany($regular_users, $company_other);

        //>project data
        $project = factory(Project::class)->create(['company_id' => $company_other->id]);
        $project->users()->attach($regular_users[0]->id, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $project_users_count = ProjectUser::count();
        //<project data

        $company_other_users_count =
            User::whereHas('companies', function ($query) use ($company_other) {
                $query->where('id', $company_other->id)
                    ->where('status', '<>', UserCompanyStatus::DELETED);
            })->count();

        $this->delete('/companies/current/users/' . $regular_users[0]->id .
            '?selected_company_id=' . $company->id)
            ->assertResponseStatus(200);

        $company_other_users_count_after_delete =
            User::whereHas('companies', function ($query) use ($company_other) {
                $query->where('id', $company_other->id)
                    ->where('status', '<>', UserCompanyStatus::DELETED);
            })->count();

        $no_touch_company_user = $regular_users[0]
            ->userCompanies()
            ->inCompany($company_other)
            ->first();

        $this->assertSame($company_other_users_count, $company_other_users_count_after_delete);
        $this->assertSame(UserCompanyStatus::APPROVED, $no_touch_company_user->status);
        $this->assertSame($project_users_count, ProjectUser::count());
    }

    /** @test */
    public function delete_response_structure()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $regular_users = factory(User::class, 2)->create();
        $this->assignUsersToCompany($regular_users, $company);

        $this->delete('/companies/current/users/' . $regular_users[0]->id .
            '?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
                'data',
                'exec_time',
            ]);
    }

    /** @test */
    public function delete_myself_soft_storing_in_database()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $regular_users = factory(User::class, 2)->create();
        $this->assignUsersToCompany($regular_users, $company);

        //>project data
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $other_company = factory(Company::class)->create();
        $project2 = factory(Project::class)->create(['company_id' => $other_company->id]);
        $project2->users()->attach($this->user, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $project_users_count = ProjectUser::count();
        //<project data

        $company_users_count = User::whereHas('companies', function ($query) use ($company) {
            $query->where('id', $company->id)
                ->where('status', '<>', UserCompanyStatus::DELETED);
        })->count();

        $this->delete('/companies/current/users/' . $this->user->id .
            '?selected_company_id=' . $company->id)
            ->assertResponseStatus(420);

        $decrese_users_count = User::whereHas('companies', function ($query) use ($company) {
            $query->where('id', $company->id)
                ->where('status', '<>', UserCompanyStatus::DELETED);
        })->count();

        $this->assertSame($company_users_count, $decrese_users_count);
        $this->assertSame($project_users_count, ProjectUser::count());
    }

    protected function add_user_to_company(
        Company $company,
        $role = RoleType::DEVELOPER,
        $status = UserCompanyStatus::APPROVED,
        $user_id = null
    ) {
        if (null === $user_id) {
            $user = factory(User::class)->create();
            $user_id = $user->id;
        }

        $userCompany = new UserCompany();
        $userCompany->user_id = $user_id;
        $userCompany->role_id = Role::findByName($role)->id;
        $userCompany->status = $status;
        $userCompany->company_id = $company->id;
        $userCompany->save();

        return $userCompany;
    }

    protected function userCompanyToArray(UserCompany $userCompany)
    {
        return [
            'id' => $userCompany->user->id,
            'email' => $userCompany->user->email,
            'first_name' => $userCompany->user->first_name,
            'last_name' => $userCompany->user->last_name,
            'avatar' => $userCompany->user->avatar,
            'company_role_id' => $userCompany->role_id,
            'company_status' => $userCompany->status,
            'company_title' => $userCompany->title,
            'company_skills' => $userCompany->skills,
            'company_description' => $userCompany->description,
        ];
    }
}
