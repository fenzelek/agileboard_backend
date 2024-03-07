<?php

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class KnowledgeDirectoryControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    protected $company;
    protected $now;
    protected $project;
    protected $request_data;
    protected $developer;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        $this->be($this->user);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $this->developer = factory(User::class)->create();
        $this->assignUsersToCompany(User::where('id', $this->developer->id)->get(), $this->company);

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);

        $this->request_data = [
            'name' => 'Test',
        ];
    }

    /** @test */
    public function store_it_doesnt_create_directory_by_company_admin()
    {
        $this->assertCount(0, KnowledgeDirectory::all());
        $this->post(
            'project/' . $this->project->id
            . '/directories?selected_company_id=' . $this->company->id,
            $this->request_data
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_it_creates_directory_by_project_admin_with_success()
    {
        $project_admin = $this->developer;
        $this->setProjectRole($this->project, RoleType::ADMIN, $project_admin);

        $this->assertCount(0, KnowledgeDirectory::all());
        $this->be($project_admin);
        $this->post(
            'project/' . $this->project->id .
            '/directories?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->verifyStoreDirectory($response->data);
    }

    /** @test */
    public function store_it_creates_directory_by_project_developer_with_success()
    {
        $project_admin = $this->developer;
        $this->setProjectRole($this->project, RoleType::DEVELOPER, $project_admin);

        $this->assertCount(0, KnowledgeDirectory::all());
        $this->be($project_admin);
        $this->post(
            'project/' . $this->project->id .
            '/directories?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->verifyStoreDirectory($response->data);
    }

    /** @test */
    public function store_it_creates_directory_by_company_developer_with_success()
    {
        $this->assertCount(0, KnowledgeDirectory::all());
        $this->be($this->developer);
        $this->post(
            'project/' . $this->project->id .
            '/directories?selected_company_id=' . $this->company->id,
            $this->request_data
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);

        $this->assertCount(0, KnowledgeDirectory::all());
    }

    /** @test */
    public function store_sending_empty_array_will_throw_validation_error()
    {
        $this->assertCount(0, KnowledgeDirectory::all());
        $this->setProjectRole($this->project, RoleType::DEVELOPER);

        $this->post(
            'project/' . $this->project->id .
            '/directories?selected_company_id=' . $this->company->id,
            []
        )->seeStatusCode(422);
        $this->verifyValidationResponse([
            'name',
        ]);

        $this->assertCount(0, KnowledgeDirectory::all());
    }

    /** @test */
    public function store_it_creates_directory_and_add_users_with_success()
    {
        $this->assertCount(0, KnowledgeDirectory::all());

        // create users and attach them to project
        $some_users = factory(User::class, 3)->create();
        $this->assignUsersToCompany($some_users, $this->company);
        $this->project->users()->attach($some_users, [
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
        ]);
        $this->setProjectRole($this->project, RoleType::DEVELOPER);

        // Add users id to request
        $this->request_data['users'] = $some_users->pluck('id')->toArray();

        $this->post(
            'project/' . $this->project->id .
            '/directories?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response_data = $this->response->getData()->data;

        // Verify added page
        $this->assertCount(1, KnowledgeDirectory::all());
        $this->verifyStoreDirectory($response_data);

        // Verify users added to page
        $response_users = collect($response_data->users->data)->sortBy('id');
        $this->assertCount(3, $response_users);
        $this->assertEquals($some_users->sortBy('id')->pluck('id'), $response_users->pluck('id'));
        $page = KnowledgeDirectory::find($response_data->id);
        $this->assertEquals($some_users->sortBy('id')->pluck('id'), $page->users->pluck('id'));
    }

    /** @test */
    public function store_it_creates_directory_for_developer_outside_of_project_with_error()
    {
        $this->assertCount(0, KnowledgeDirectory::all());
        $this->setProjectRole($this->project, RoleType::DEVELOPER);
        // create user and attach it to project
        $user_in_company = factory(User::class)->create();
        $this->assignUsersToCompany(User::where('id', $user_in_company->id)->get(), $this->company);

        // Add user id to request
        $this->request_data['users'] = [$user_in_company->id];

        $this->post(
            'project/' . $this->project->id .
            '/directories?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(422);
        $this->verifyValidationResponse([
            'users.0',
        ]);
    }

    /** @test */
    public function store_it_creates_directory_and_add_roles_with_success()
    {
        $this->assertCount(0, KnowledgeDirectory::all());
        $this->setProjectRole($this->project, RoleType::DEVELOPER);

        // add company roles
        $roles_id = [
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEVELOPER)->id,
        ];
        $this->company->roles()->attach($roles_id);

        // Add roles id to request
        $this->request_data['roles'] = $roles_id;

        $this->post(
            'project/' . $this->project->id .
            '/directories?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response_data = $this->response->getData()->data;

        // Verify added page
        $this->assertCount(1, KnowledgeDirectory::all());
        $this->verifyStoreDirectory($response_data);

        // Verify roles added to page
        $response_roles = collect($response_data->roles->data)->sortBy('id');
        $this->assertCount(2, $response_roles);
        $this->assertEquals($roles_id, $response_roles->pluck('id')->toArray());
        $page = KnowledgeDirectory::find($response_data->id);
        $this->assertEquals($roles_id, $page->roles->pluck('id')->toArray());
    }

    /** @test */
    public function store_it_creates_directory_and_add_wrong_role_with_error()
    {
        $this->assertCount(0, KnowledgeDirectory::all());
        $this->setProjectRole($this->project, RoleType::DEVELOPER);

        // add company roles
        $roles_id = [
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEVELOPER)->id,
        ];
        $this->company->roles()->attach($roles_id);
        // Role not in company roles
        $wrong_role = Role::findByName(RoleType::CLIENT)->id;

        // Add roles id to request
        $this->request_data['roles'] = [$wrong_role];

        $this->post(
            'project/' . $this->project->id .
            '/directories?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(422);
        $this->verifyValidationResponse([
            'roles.0',
        ]);
    }

    /** @test */
    public function delete_it_deletes_directory_by_admin_with_success()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $this->setProjectRole($this->project, RoleType::DEVELOPER);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204);
        $this->assertCount(0, KnowledgeDirectory::all());
    }

    /** @test */
    public function delete_it_deletes_directory_by_admin_in_project_with_success()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $project_admin = $this->developer;
        $admin_role_id = Role::findByName(RoleType::ADMIN)->id;
        $project_admin->projects()->attach($this->project, ['role_id' => $admin_role_id]);
        $this->be($project_admin);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204);
        $this->assertCount(0, KnowledgeDirectory::all());
    }

    /** @test */
    public function delete_it_deletes_directory_by_developer_in_project_with_success()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $project_developer = $this->developer;
        $developer_role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $developer_role_id]);
        $this->be($project_developer);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204);
        $this->assertCount(0, KnowledgeDirectory::all());
    }

    /** @test */
    public function delete_it_deletes_directory_by_developer_not_in_project_with_error()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $this->be($this->developer);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
        $this->assertCount(1, KnowledgeDirectory::all());
    }

    /** @test */
    public function delete_it_deletes_directory_with_role_permissions_with_success()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        // attach role to directory
        $directory->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204);
        $this->assertCount(0, KnowledgeDirectory::all());
    }

    /** @test */
    public function delete_it_deletes_directory_with_role_permissions_with_error()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        // attach role to directory
        $directory->roles()->attach(Role::findByName(RoleType::CLIENT)->id);

        // Attach user to project with developer role
        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
        $this->assertCount(1, KnowledgeDirectory::all());
    }

    /** @test */
    public function delete_it_deletes_directory_with_user_permissions_with_success()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $this->developer->projects()
            ->attach($this->project, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $directory->users()->attach($this->developer);

        $this->be($this->developer);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204);
        $this->assertCount(0, KnowledgeDirectory::all());
    }

    /** @test */
    public function delete_it_deletes_directory_with_user_permissions_with_error()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $this->developer->projects()
            ->attach($this->project, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $directory->users()->attach($this->user);

        $this->be($this->developer);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
        $this->assertCount(1, KnowledgeDirectory::all());
    }

    /** @test */
    public function delete_it_deletes_directory_and_move_pages_to_other_directory()
    {
        $this->setProjectRole($this->project, RoleType::DEVELOPER);

        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $other_directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $pages = factory(KnowledgePage::class, 2)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => $directory->id,
        ]);

        $this->assertCount(2, KnowledgeDirectory::all());
        $this->assertEquals($directory->id, $pages[0]->directory->id);
        $this->assertEquals($directory->id, $pages[1]->directory->id);

        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id,
            ['knowledge_directory_id' => $other_directory->id]
        )->seeStatusCode(204);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->assertEquals($other_directory->id, $pages[0]->fresh()->directory->id);
        $this->assertEquals($other_directory->id, $pages[1]->fresh()->directory->id);
    }

    /** @test */
    public function delete_it_deletes_directory_and_move_pages_to_other_directory_with_error()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $other_directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $pages = factory(KnowledgePage::class, 2)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => $directory->id,
        ]);
        // attach role to directory
        $other_directory->roles()->attach(Role::findByName(RoleType::CLIENT)->id);

        // Attach user to project with developer role
        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->assertCount(2, KnowledgeDirectory::all());
        $this->assertEquals($directory->id, $pages[0]->directory->id);
        $this->assertEquals($directory->id, $pages[1]->directory->id);

        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id,
            ['knowledge_directory_id' => $other_directory->id]
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);

        $this->assertCount(2, KnowledgeDirectory::all());
    }

    /** @test */
    public function delete_it_deletes_directory_and_move_pages_to_project()
    {
        $this->setProjectRole($this->project, RoleType::DEVELOPER);

        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $pages = factory(KnowledgePage::class, 2)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => $directory->id,
        ]);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->assertEquals($directory->id, $pages[0]->directory->id);
        $this->assertEquals($directory->id, $pages[1]->directory->id);

        $this->delete(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204);

        $this->assertCount(0, KnowledgeDirectory::all());
        $this->assertEquals(null, $pages[0]->fresh()->directory);
        $this->assertEquals(null, $pages[1]->fresh()->directory);
    }

    /** @test */
    public function update_it_updates_directory()
    {
        $directory = $this->updateSetUp();

        $this->setProjectRole($this->project, RoleType::DEVELOPER);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->assertNotEquals('Test', $directory->name);
        $this->put(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->assertEquals('Test', $response->name);
    }

    /** @test */
    public function update_it_updates_directory_for_developer()
    {
        $directory = $this->updateSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->assertNotEquals('Test', $directory->name);
        $this->put(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->assertEquals('Test', $response->name);
    }

    /** @test */
    public function update_it_updates_directory_with_role_restriction_for_developer()
    {
        $directory = $this->updateSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);
        $directory->roles()->attach($role_id);

        $client_role_id = Role::findByName(RoleType::CLIENT)->id;
        $this->company->roles()->attach($client_role_id);
        $this->request_data['roles'] = [$client_role_id];

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->assertNotEquals('Test', $directory->name);
        $this->put(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->assertEquals('Test', $response->name);
        $this->assertEquals('client', $response->roles->data[0]->name);
    }

    /** @test */
    public function update_it_updates_directory_with_role_restriction_for_developer_with_401_error()
    {
        $directory = $this->updateSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);
        $directory->roles()->attach(Role::findByName(RoleType::CLIENT)->id);

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->assertNotEquals('Test', $directory->name);
        $this->put(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function update_it_updates_directory_with_user_restriction_for_developer()
    {
        $directory = $this->updateSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->user->projects()->attach($this->project);
        $this->be($project_developer);
        $directory->users()->attach($project_developer);

        $this->request_data['users'] = [$this->user->id];

        $this->assertCount(1, KnowledgeDirectory::all());
        $this->assertNotEquals('Test', $directory->name);
        $this->put(
            'project/' . $this->project->id
            . '/directories/' . $directory->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->assertEquals('Test', $response->name);
        $this->assertEquals($this->user->id, $response->users->data[0]->id);
    }

    /** @test */
    public function index_it_lists_directories()
    {
        $directories = $this->indexSetUp();
        $this->setProjectRole($this->project, RoleType::ADMIN);

        $page_1 = factory(KnowledgePage::class)->create([
            'knowledge_directory_id' => $directories[0]->id,
            'project_id' => $this->project->id,
        ]);
        $page_2 = factory(KnowledgePage::class)->create(
            [
                'knowledge_directory_id' => $directories[0]->id,
                'project_id' => $this->project->id,
            ]
        );

        $this->get(
            'project/' . $this->project->id . '/directories/'
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->assertCount(3, $response);
        $this->assertEquals(
            $directories->sortBy('name')->pluck('id'),
            collect($response)->pluck('id')
        );
        $this->assertEquals(
            $directories->sortBy('name')->pluck('name'),
            collect($response)->pluck('name')
        );

        //relations
        foreach ($response as $dir) {
            $this->assertTrue(count($dir->pages->data) || count($dir->roles->data) ||
                count($dir->users->data));
            if ($dir->pages->data) {
                $this->assertTrue(count($dir->pages->data) == 2);
                $this->assertSame($dir->pages->data[0]->id, $page_1->id);
                $this->assertSame($dir->pages->data[0]->name, $page_1->name);
                $this->assertSame($dir->pages->data[1]->id, $page_2->id);
                $this->assertSame($dir->pages->data[1]->name, $page_2->name);
            }
            if (count($dir->roles->data)) {
                $this->assertSame($dir->roles->data[0]->name, RoleType::DEVELOPER);
            }
            if (count($dir->users->data)) {
                $this->assertSame($dir->users->data[0]->id, $this->developer->id);
            }
        }
    }

    /** @test */
    public function index_it_lists_directories_with_pages_that_user_has_access_to()
    {
        $directories = $this->indexSetUp();
        $expected_directories = collect([$directories[0], $directories[1]]);

        $this->setProjectRole($this->project, RoleType::DEVELOPER);

        $pages = factory(KnowledgePage::class, 4)->create(
            [
                'knowledge_directory_id' => $directories[0]->id,
                'project_id' => $this->project->id,
            ]
        );

        // 1st page has this user attached
        $pages[0]->users()->attach($this->user->id);
        // 2nd page has other role assigned
        $pages[1]->roles()->attach(Role::findByName(RoleType::ADMIN)->id);
        // 3rd page has this user's project role assigned
        $pages[2]->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);
        // 4th page has no restrictions

        $this->get(
            'project/' . $this->project->id . '/directories/'
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->assertCount(count($expected_directories), $response);
        $this->assertEquals(
            $expected_directories->sortBy('name')->pluck('id'),
            collect($response)->pluck('id')
        );
        $this->assertEquals(
            $expected_directories->sortBy('name')->pluck('name'),
            collect($response)->pluck('name')
        );

        $directory_with_pages = collect($response)->first(function ($directory) use ($directories) {
            return $directory->id == $directories[0]->id;
        });

        // make sure we have valid number of pages and we have pages we expect
        $this->assertCount(3, $directory_with_pages->pages->data);
        $this->assertEqualsCanonicalizing(
            [$pages[0]->id, $pages[2]->id, $pages[3]->id],
            collect($directory_with_pages->pages->data)->pluck('id')->all(),
            '',
            0,
            10,
            true
        );
    }

    /** @test */
    public function index_it_lists_directories_for_developer()
    {
        $directories = $this->indexSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->get(
            'project/' . $this->project->id . '/directories/'
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->assertCount(3, $response);
        $this->assertEquals(
            $directories->sortBy('name')->pluck('id'),
            collect($response)->pluck('id')
        );
        $this->assertEquals(
            $directories->sortBy('name')->pluck('name'),
            collect($response)->pluck('name')
        );
    }

    /** @test */
    public function index_it_checks_roles_restriction()
    {
        $directories = $this->indexSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEALER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->get(
            'project/' . $this->project->id . '/directories/'
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->assertCount(2, $response);
        $directories = $directories->filter(function ($val) {
            return $val->roles->isEmpty();
        });
        $this->assertEquals(
            $directories->sortBy('name')->pluck('id'),
            collect($response)->pluck('id')
        );
        $this->assertEquals(
            $directories->sortBy('name')->pluck('name'),
            collect($response)->pluck('name')
        );
    }

    /** @test */
    public function index_it_checks_users_restriction()
    {
        $directories = $this->indexSetUp();

        $user = factory(User::class)->create();
        $this->assignUsersToCompany(User::where('id', $user->id)->get(), $this->company);
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $user->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($user);

        $this->get(
            'project/' . $this->project->id . '/directories/'
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->assertCount(2, $response);
        $directories = $directories->filter(function ($val) {
            return $val->users->isEmpty();
        });
        $this->assertEquals(
            $directories->sortBy('name')->pluck('id'),
            collect($response)->pluck('id')
        );
        $this->assertEquals(
            $directories->sortBy('name')->pluck('name'),
            collect($response)->pluck('name')
        );
    }

    /** @test */
    public function index_it_checks_all_restrictions()
    {
        $directories = $this->indexSetUp();

        $user = factory(User::class)->create();
        $this->assignUsersToCompany(User::where('id', $user->id)->get(), $this->company);
        $role_id = Role::findByName(RoleType::DEALER)->id;
        $user->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($user);

        $this->get(
            'project/' . $this->project->id . '/directories/'
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->assertCount(1, $response);
        $directories = $directories->filter(function ($val) {
            return $val->users->isEmpty() && $val->roles->isEmpty();
        });
        $this->assertEquals(
            $directories->sortBy('name')->pluck('id'),
            collect($response)->pluck('id')
        );
        $this->assertEquals(
            $directories->sortBy('name')->pluck('name'),
            collect($response)->pluck('name')
        );
    }

    protected function verifyStoreDirectory($data)
    {
        $user_id = auth()->user()->id;
        $this->assertSame($this->project->id, $data->project_id);
        $this->assertSame($user_id, $data->creator_id);
        $this->assertSame('Test', $data->name);

        $directory = KnowledgeDirectory::find($data->id);

        $this->assertSame($this->project->id, $directory->project_id);
        $this->assertSame($user_id, $directory->creator_id);
        $this->assertSame('Test', $directory->name);
    }

    protected function updateSetUp()
    {
        return factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
            'name' => 'Not test',
        ]);
    }

    protected function indexSetUp()
    {
        $dirs = factory(KnowledgeDirectory::class, 3)->create([
            'project_id' => $this->project->id,
        ]);

        $dirs[1]->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);
        $dirs[2]->users()->attach($this->developer->id);

        return $dirs;
    }
}
