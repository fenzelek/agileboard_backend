<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers;

use App\Models\Db\Package;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Db\Role;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Helpers\ErrorCode;
use Carbon\Carbon;
use Tests\BrowserKitTestCase;

class UserControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    protected $company;
    protected $now;
    protected $developer;
    protected $project;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->developer = factory(User::class)->create();
        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_FREE);
        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        // Add admin and developer role to company roles
        $this->company->roles()->attach([2, 4]);
    }

//    public function store_admin_attach_user_to_project_with_too_many_users()
//    {
//        $developer_role = Role::findByName(RoleType::DEVELOPER);
//        $users = factory(User::class, 3)->create();
//        $this->assignUsersToCompany($users[0]->get(), $this->company, RoleType::DEVELOPER);
//        $this->assignUsersToCompany($users[1]->get(), $this->company, RoleType::DEVELOPER);
//        $this->assignUsersToCompany($users[2]->get(), $this->company, RoleType::DEVELOPER);
//        $this->assignUsersToCompany($this->developer->get(), $this->company, RoleType::DEVELOPER);
//
//        $this->project->users()->sync([
//            $users[0]->id => ['role_id' => $developer_role->id],
//            $users[1]->id => ['role_id' => $developer_role->id],
//            $users[2]->id => ['role_id' => $developer_role->id],
//        ]);
//
//        $this->assertCount(3, $this->project->users()->get());
//
//        $this->post('/projects/' . $this->project->id . '/users/?selected_company_id=' .
//            $this->company->id, [
//            'user_id' => $this->developer->id,
//            'role_id' => 4,
//        ]);
//
//        $this->verifyErrorResponse(410, ErrorCode::PACKAGE_TOO_MANY_USERS);
//        $this->assertCount(3, $this->project->users()->get());
//    }

    /** @test */
    public function store_admin_attach_user_to_project_with_success()
    {
        $this->assignUsersToCompany($this->developer->get(), $this->company, RoleType::DEVELOPER);
        $this->assertCount(0, $this->project->users()->get());

        // Attach user to project
        $response = $this->post('/projects/' . $this->project->id . '/users/?selected_company_id=' .
            $this->company->id, [
            'user_id' => $this->developer->id,
            'role_id' => 4,
        ])->seeStatusCode(201)->response->getData()->data;

        // Check if user is attached to project and has role
        $this->assertCount(1, $this->project->users()->get());
        $this->verifyStoreResponse($response->data);
    }

    /** @test */
    public function store_admin_attach_user_to_closed_project_with_success()
    {
        //create closed project
        $this->project = factory(Project::class)->create([
            'company_id' => $this->company->id,
            'closed_at' => $this->now->toDateTimeString(),
        ]);
        $this->assignUsersToCompany($this->developer->get(), $this->company, RoleType::DEVELOPER);
        $this->assertCount(0, $this->project->users()->get());

        // Attach user to project
        $response = $this->post('/projects/' . $this->project->id . '/users/?selected_company_id=' .
            $this->company->id, [
            'user_id' => $this->developer->id,
            'role_id' => 4,
        ])->seeStatusCode(201)->response->getData()->data;

        // Check if user is attached to project and has role
        $this->assertCount(1, $this->project->users()->get());
        $this->verifyStoreResponse($response->data);
    }

    /** @test */
    public function store_admin_attach_user_to_project_from_other_company_get_404_error()
    {
        $this->assignUsersToCompany($this->developer->get(), $this->company, RoleType::DEVELOPER);
        // create new company and add project to it
        $new_company = factory(Company::class)->create();
        $this->project = factory(Project::class)->create(['company_id' => $new_company->id]);

        // Attach user to project
        $this->post('/projects/' . $this->project->id . '/users/?selected_company_id=' .
            $this->company->id, [
            'user_id' => $this->developer->id,
            'role_id' => 4,
        ])->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_developer_attach_user_to_project_get_401_error()
    {
        $this->assignUsersToCompany($this->developer->get(), $this->company, RoleType::DEVELOPER);
        $this->be($this->developer);

        $this->post('/projects/' . $this->project->id . '/users/?selected_company_id=' .
            $this->company->id, [
            'user_id' => $this->user->id,
            'role_id' => 4,
        ])->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_admin_attach_user_not_in_company_to_project_get_validation_error()
    {
        $this->post('/projects/' . $this->project->id . '/users/?selected_company_id=' .
            $this->company->id, [
            'user_id' => $this->developer->id,
            'role_id' => 4,
        ]);
        $this->verifyValidationResponse([
            'user_id',
        ]);
    }

    /** @test */
    public function store_admin_attach_user_with_wrong_role_id_get_validation_error()
    {
        $this->post('/projects/' . $this->project->id . '/users/?selected_company_id=' .
            $this->company->id, [
            'user_id' => $this->developer->id,
            'role_id' => 5,
        ]);
        $this->verifyValidationResponse([
            'role_id',
        ]);
    }

    /** @test */
    public function store_admin_send_empty_data_get_validation_error()
    {
        $this->post('/projects/' . $this->project->id . '/users/?selected_company_id=' .
            $this->company->id);
        $this->verifyValidationResponse([
            'user_id',
            'role_id',
        ]);
    }

    /** @test */
    public function destroy_admin_detach_user_from_project_with_success()
    {
        // Assign developer to company
        $this->assignUsersToCompany($this->developer->get(), $this->company, RoleType::DEVELOPER);
        $this->assertCount(0, $this->project->users()->get());
        // Attach user to project
        $this->attachUser($this->developer, $this->project, RoleType::DEVELOPER);
        // Check if user is attached to project
        $this->assertCount(1, $this->project->users()->get());
        $this->assertSame(
            $this->developer->id,
            $this->project->users()->find($this->developer->id)->id
        );

        // Detach user from project
        $this->delete('/projects/' . $this->project->id .
            '/users/' . $this->developer->id .
            '/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(204);

        // test if user is really detached
        $this->assertCount(0, $this->project->users()->get());
    }

    /** @test */
    public function destroy_admin_detach_user_from_closed_project_with_success()
    {
        //create closed project and assign to company
        $this->project = factory(Project::class)->create([
            'company_id' => $this->company->id,
            'closed_at' => $this->now->toDateTimeString(),
        ]);
        $this->assignUsersToCompany($this->developer->get(), $this->company, RoleType::DEVELOPER);
        // Check if there are no users assign to the project
        $this->assertCount(0, $this->project->users()->get());
        // Attach user to project
        $this->attachUser($this->developer, $this->project, RoleType::DEVELOPER);
        // Check if user is attached to project
        $this->assertCount(1, $this->project->users()->get());
        $this->assertSame(
            $this->developer->id,
            $this->project->users()->find($this->developer->id)->id
        );

        // Detach user from project
        $this->delete('/projects/' . $this->project->id .
            '/users/' . $this->developer->id .
            '/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(204);

        // test if user is really detached
        $this->assertCount(0, $this->project->users()->get());
    }

    /** @test */
    public function destroy_admin_detach_user_from_project_in_other_company_get_404_error()
    {
        // create new company and add project to it
        $new_company = factory(Company::class)->create();
        $this->project = factory(Project::class)->create(['company_id' => $new_company->id]);
        // add user to company and project
        $this->assignUsersToCompany($this->developer->get(), $new_company, RoleType::DEVELOPER);
        $this->attachUser($this->developer, $this->project, RoleType::DEVELOPER);

        // Detach user from project
        $this->delete(
            '/projects/' . $this->project->id .
            '/users/' . $this->developer->id .
            '/?selected_company_id=' . $this->company->id
        )->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function destroy_developer_detach_user_from_project_get_401_error()
    {
        $this->assignUsersToCompany($this->developer->get(), $this->company, RoleType::DEVELOPER);
        $this->be($this->developer);

        $this->delete(
            '/projects/' . $this->project->id .
            '/users/' . $this->user->id .
            '/?selected_company_id=' . $this->company->id
        )->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_it_show_users_list_in_project_for_admin_with_success()
    {
        $project_users = collect($this->usersSetUp())->sortBy('id');

        $this->get('/projects/' . $this->project->id .
            '/users/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure($this->indexResponseStructure());

        $response_data = collect($this->response->getData()->data)->sortBy('id');

        // There should be 2 users in project
        $this->assertCount(2, $response_data);
        $this->verifyIndexResponse($project_users, $response_data);
    }

    /** @test */
    public function index_it_allows_to_get_users_when_searching()
    {
        $users = collect([
            factory(User::class)->create(['email' => 'foo@example.com']),
            factory(User::class)->create(['first_name' => 'Mister Foo']),
            factory(User::class)->create(['last_name' => 'Fool']),
            factory(User::class)->create([
                'first_name' => 'Mister Sample',
                'email' => 'baz@example.com',
                'last_name' => 'Sample',
            ]),
            factory(User::class)->create([
                'first_name' => 'Mister Baz',
                'email' => 'sample@example.com',
                'last_name' => 'Sample',
            ]),
            factory(User::class)->create([
                'first_name' => 'Mister Sample',
                'email' => 'sample2@example.com',
                'last_name' => 'Baz',
            ]),
            factory(User::class)->create(['email' => 'bar@example.com']),
            factory(User::class)->create(['first_name' => 'Mister Bar']),
            factory(User::class)->create(['last_name' => 'Bar']),
        ]);
        $this->project->users()->attach($users->pluck('id')->all());

        $this->get('/projects/' . $this->project->id .
            '/users/?selected_company_id=' . $this->company->id . '&search=foo bar')
            ->seeStatusCode(200);

        $response_data = collect($this->response->getData()->data);

        // There should be 6 users in project
        $this->assertCount(6, $response_data);

        // make sure only those containing foo and bar are present and not those with baz
        $response_users = $response_data->pluck('user.data.id');

        $this->assertTrue($response_users->contains($users->get(0)->id));
        $this->assertTrue($response_users->contains($users->get(1)->id));
        $this->assertTrue($response_users->contains($users->get(2)->id));
        $this->assertTrue($response_users->contains($users->get(6)->id));
        $this->assertTrue($response_users->contains($users->get(7)->id));
        $this->assertTrue($response_users->contains($users->get(8)->id));
    }

    /** @test */
    public function index_it_allows_to_get_info_only_for_single_user()
    {
        $users = factory(User::class, 6)->create();
        $this->project->users()->attach($users->pluck('id')->all());

        $this->get('/projects/' . $this->project->id .
            '/users/?selected_company_id=' . $this->company->id . '&user_id=' . $users[3]->id)
            ->seeStatusCode(200);

        $response_data = collect($this->response->getData()->data);

        // There should be 1 user in project - the one with selected user id
        $this->assertCount(1, $response_data);

        // make sure only one containing user id will be returned
        $response_users = $response_data->pluck('user.data.id');

        $this->assertTrue($response_users->contains($users->get(3)->id));
    }

    /** @test */
    public function index_it_allows_to_get_info_only_for_current_user()
    {
        $users = factory(User::class, 6)->create();
        $this->project->users()->attach($users->pluck('id')->all());
        $this->project->users()->attach($this->user->id, [
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
        ]);

        $this->get('/projects/' . $this->project->id .
            '/users/?selected_company_id=' . $this->company->id . '&user_id=current')
            ->seeStatusCode(200)
            ->seeJsonStructure($this->indexResponseStructure(true));

        $response_data = collect($this->response->getData()->data);

        // There should be 1 user in project - the one with selected user id
        $this->assertCount(1, $response_data);

        // make sure only one containing user id will be returned
        $response_users = $response_data->pluck('user.data.id');

        $this->assertTrue($response_users->contains($this->user->id));
    }

    /** @test */
    public function index_it_show_users_list_in_project_for_developer_in_project_with_success()
    {
        $project_users = collect($this->usersSetUp())->sortBy('id');
        $this->be($project_users[0]);

        $this->get('/projects/' . $this->project->id .
            '/users/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure($this->indexResponseStructure());

        $response_data = collect($this->response->getData()->data)->sortBy('id');

        // There should be 2 users in project
        $this->assertCount(2, $response_data);
        $this->verifyIndexResponse($project_users, $response_data);
    }

    /** @test */
    public function index_it_show_users_list_in_project_for_developer_not_in_project_with_error()
    {
        $this->usersSetUp();
        $this->be($this->developer);

        $this->get('/projects/' . $this->project->id .
            '/users/?selected_company_id=' . $this->company->id)
            ->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_it_show_users_list_in_project_for_admin_not_in_company_with_error()
    {
        $this->usersSetUp();

        $new_company = factory(Company::class)->create();
        $new_admin = factory(User::class)->create();

        $this->assignUsersToCompany($new_admin->get(), $new_company, RoleType::ADMIN);

        $this->be($new_admin);

        $this->get('/projects/' . $this->project->id .
            '/users/?selected_company_id=' . $this->company->id)
            ->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    protected function verifyStoreResponse($response)
    {
        $this->assertSame($this->project->id, $response->project_id);
        $this->assertSame($this->developer->id, $response->user_id);
        $this->assertSame(4, $response->role_id);
        $this->assertSame($this->now->toDateTimeString(), $response->created_at);
        $this->assertSame($this->now->toDateTimeString(), $response->updated_at);
    }

    protected function usersSetUp()
    {
        // Create 2 users and put them in project
        $users_in_project = factory(User::class, 2)->create();
        $this->attachUser(
            [$users_in_project[0]->id, $users_in_project[1]->id],
            $this->project,
            RoleType::DEVELOPER
        );

        // Create user not in company
        factory(User::class)->create();

        // Assign developer and 2 project users to company
        $this->assignUsersToCompany(collect([
            $this->developer,
            $users_in_project[0],
            $users_in_project[1],
        ]), $this->company, RoleType::DEVELOPER);

        // 2 Users in project and company ($users_in_project)
        // 2 Users not in project but in company ($this->developer and $this->admin)
        // 1 User not in company
        return $users_in_project;
    }

    protected function verifyIndexResponse($project_users, $response_data)
    {
        $this->assertEquals($project_users->pluck('id'), $response_data->pluck('user_id'));
        $this->assertEquals(
            $project_users->pluck('email'),
            $response_data->pluck('user.data.email')
        );
        $this->assertEquals(
            $project_users->pluck('first_name'),
            $response_data->pluck('user.data.first_name')
        );
        $this->assertEquals(
            $project_users->pluck('last_name'),
            $response_data->pluck('user.data.last_name')
        );
        foreach ($response_data as $data) {
            $this->assertEquals($this->now->toDateTimeString(), $data->created_at);
            $this->assertEquals(RoleType::DEVELOPER, $data->role->data->name);
        }
    }

    protected function indexResponseStructure($with_permission = false)
    {
        $structure = [
            'data' => [
                [
                    'id',
                    'user_id',
                    'project_id',
                    'role_id',
                    'created_at',
                    'updated_at',
                    'user' => [
                        'data' => [
                            'id',
                            'email',
                            'first_name',
                            'last_name',
                            'avatar',
                        ],
                    ],
                    'role' => [
                        'data' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ],
            'exec_time',
        ];

        if ($with_permission) {
            $structure['data'][0]['project_permission'] = [
                'ticket_show',
                'ticket_create',
                'ticket_update',
                'ticket_destroy',
                'ticket_comment_create',
                'ticket_comment_update',
                'ticket_comment_destroy',
            ];
        }

        return $structure;
    }

    protected function attachUser($user, $project, $role)
    {
        $role_id = Role::findByName($role)->id;
        $project->users()->attach($user, ['role_id' => $role_id]);
    }
}
