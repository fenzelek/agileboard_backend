<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController;

use App\Models\Db\Package;
use App\Models\Db\ProjectPermission;
use App\Models\Db\ProjectUser;
use App\Models\Db\Role;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Helpers\ErrorCode;
use Carbon\Carbon;
use Tests\BrowserKitTestCase;

class StoreTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    protected $name;
    protected $short_name;
    protected $first_number_of_tickets;
    protected $time_tracking_visible_for_clients;
    protected $language;
    protected $email_notification_enabled;
    protected $slack_notification_enabled;
    protected $slack_webhook_url;
    protected $slack_channel;
    protected $color;
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

        $this->name = 'Projekt Testowy';
        $this->short_name = 'Test';
        $this->first_number_of_tickets = 10;
        $this->time_tracking_visible_for_clients = 1;
        $this->language = 'pl';
        $this->email_notification_enabled = 1;
        $this->slack_notification_enabled = 1;
        $this->slack_webhook_url = 'http://example.pl';
        $this->slack_channel = 'test';
        $this->color = 'test';

        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_FREE);

        // make sure all roles are assigned into this company
        $this->company->roles()->sync(Role::all()->pluck('id'));

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $this->project->users()->attach($this->user);
    }

//    public function store_it_creates_project_with_too_many_users()
//    {
//        $initial_project_users_count = ProjectUser::count();
//
//        // check if there are no projects with this name
//        $this->assertEmpty($this->user->fresh()->projects->where('name', $this->name));
//
//        $users = factory(User::class, 8)->create();
//        foreach ($users as $user) {
//            $user->companies()->attach($this->company->id);
//        }
//
//        $owner_role = Role::findByName(RoleType::OWNER);
//        $admin_role = Role::findByName(RoleType::ADMIN);
//        $seller_role = Role::findByName(RoleType::DEALER);
//        $developer_role = Role::findByName(RoleType::DEVELOPER);
//        $client_role = Role::findByName(RoleType::CLIENT);
//
//        $this->post('/projects/?selected_company_id=' . $this->company->id, [
//            'name' => $this->name,
//            'short_name' => $this->short_name,
//            'first_number_of_tickets' => $this->first_number_of_tickets,
//            'time_tracking_visible_for_clients' => $this->time_tracking_visible_for_clients,
//            'language' => $this->language,
//            'email_notification_enabled' => $this->email_notification_enabled,
//            'slack_notification_enabled' => $this->slack_notification_enabled,
//            'slack_webhook_url' => $this->slack_webhook_url,
//            'slack_channel' => $this->slack_channel,
//            'color' => $this->color,
//            'users' => [
//                [
//                    'user_id' => $users[2]->id,
//                    'role_id' => $admin_role->id,
//                ],
//                [
//                    'user_id' => $users[1]->id,
//                    'role_id' => $owner_role->id,
//                ],
//                [
//                    'user_id' => $users[0]->id,
//                    'role_id' => $seller_role->id,
//                ],
//                [
//                    'user_id' => $users[3]->id,
//                    'role_id' => $developer_role->id,
//                ],
//                [
//                    'user_id' => $users[4]->id,
//                    'role_id' => $client_role->id,
//                ],
//                [
//                    'user_id' => $users[5]->id,
//                    'role_id' => $developer_role->id,
//                ],
//                [
//                    'user_id' => $users[6]->id,
//                    'role_id' => $developer_role->id,
//                ],
//            ],
//        ]);
//
//        $this->verifyErrorResponse(410, ErrorCode::PACKAGE_TOO_MANY_USERS);
//        $this->assertCount($initial_project_users_count, $this->project->users()->get());
//    }

    /** @test */
    public function store_it_creates_project_with_success()
    {
        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_CLASSIC);

        // make sure all roles are assigned into this company
        $this->company->roles()->sync(Role::all()->pluck('id'));

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $this->project->users()->attach($this->user);

        $initial_project_users_count = ProjectUser::count();

        // check if there are no projects with this name
        $this->assertEmpty($this->user->fresh()->projects->where('name', $this->name));

        $users = factory(User::class, 8)->create();
        foreach ($users as $user) {
            $user->companies()->attach($this->company->id);
        }

        $owner_role = Role::findByName(RoleType::OWNER);
        $admin_role = Role::findByName(RoleType::ADMIN);
        $seller_role = Role::findByName(RoleType::DEALER);
        $developer_role = Role::findByName(RoleType::DEVELOPER);
        $client_role = Role::findByName(RoleType::CLIENT);

        $this->post('/projects/?selected_company_id=' . $this->company->id, [
            'name' => $this->name,
            'short_name' => $this->short_name,
            'first_number_of_tickets' => $this->first_number_of_tickets,
            'time_tracking_visible_for_clients' => $this->time_tracking_visible_for_clients,
            'language' => $this->language,
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'users' => [
                [
                    'user_id' => $users[2]->id,
                    'role_id' => $admin_role->id,
                ],
                [
                    'user_id' => $users[1]->id,
                    'role_id' => $owner_role->id,
                ],
                [
                    'user_id' => $users[0]->id,
                    'role_id' => $seller_role->id,
                ],
                [
                    'user_id' => $users[3]->id,
                    'role_id' => $developer_role->id,
                ],
                [
                    'user_id' => $users[4]->id,
                    'role_id' => $client_role->id,
                ],
                [
                    'user_id' => $users[5]->id,
                    'role_id' => $developer_role->id,
                ],
                [
                    'user_id' => $users[6]->id,
                    'role_id' => $developer_role->id,
                ],
            ],
        ])->seeStatusCode(201)->seeJsonContains([
            'name' => $this->name,
            'short_name' => $this->short_name,
            'time_tracking_visible_for_clients' => $this->time_tracking_visible_for_clients,
            'language' => $this->language,
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'company_id' => $this->company->id,
            'closed_at' => null,
            'deleted_at' => null,
        ])->isJson();

        $new_project = Project::where('name', $this->name)->first();

        $this->assertSame($this->name, $new_project->name);
        $this->assertSame($this->short_name, $new_project->short_name);
        $this->assertSame($this->company->id, $new_project->company_id);
        $this->assertSame($this->first_number_of_tickets - 1, $new_project->created_tickets);
        $this->assertSame($this->time_tracking_visible_for_clients, $new_project->time_tracking_visible_for_clients);
        $this->assertSame($this->language, $new_project->language);
        $this->assertSame($this->email_notification_enabled, $new_project->email_notification_enabled);
        $this->assertSame($this->slack_notification_enabled, $new_project->slack_notification_enabled);
        $this->assertSame($this->slack_webhook_url, $new_project->slack_webhook_url);
        $this->assertSame($this->slack_channel, $new_project->slack_channel);
        $this->assertSame($this->color, $new_project->color);
        $this->assertNull($new_project->closed_at);
        $this->assertNull($new_project->deleted_at);

        // now verify users
        $this->assertSame(7 + $initial_project_users_count, ProjectUser::count());

        $this->assertCount(7, $new_project->users);

        $this->assertEquals(
            [$users[2]->id],
            $new_project->users->where('pivot.role_id', $admin_role->id)->pluck('id')->all()
        );

        $this->assertEquals(
            [$users[1]->id],
            $new_project->users->where('pivot.role_id', $owner_role->id)->pluck('id')->all()
        );

        $this->assertEquals(
            [$users[0]->id],
            $new_project->users->where('pivot.role_id', $seller_role->id)->pluck('id')->all()
        );

        $this->assertEquals(
            [$users[4]->id],
            $new_project->users->where('pivot.role_id', $client_role->id)->pluck('id')->all()
        );

        $this->assertEquals(
            [$users[3]->id, $users[5]->id, $users[6]->id],
            $new_project->users->where('pivot.role_id', $developer_role->id)->pluck('id')->all()
        );

        $this->assertNotEmpty($new_project->permission);
        $this->assertInstanceOf(ProjectPermission::class, $new_project->permission);
    }

    /** @test */
    public function store_it_gets_error_when_package_limit_reached()
    {
        // check if there are no projects with this name
        $this->assertEmpty($this->user->fresh()->projects->where('name', $this->name));

        factory(Project::class, 5)->create(['company_id' => $this->company->id]);

        $user = factory(User::class)->create();
        $user->companies()->attach($this->company->id);

        $owner_role = Role::findByName(RoleType::OWNER);
        $projectsCount = Project::all()->count();

        $this->post('/projects/?selected_company_id=' . $this->company->id, [
            'name' => $this->name,
            'short_name' => $this->short_name,
            'first_number_of_tickets' => $this->first_number_of_tickets,
            'time_tracking_visible_for_clients' => $this->time_tracking_visible_for_clients,
            'language' => $this->language,
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'users' => [
                [
                    'user_id' => $user->id,
                    'role_id' => $owner_role->id,
                ],
            ],
        ]);

        $this->verifyErrorResponse(409, ErrorCode::PACKAGE_LIMIT_REACHED);
        $this->assertCount($projectsCount, Project::all());
    }

    /** @test */
    public function store_it_gets_error_when_user_is_duplicated()
    {
        // check if there are no projects with this name
        $this->assertEmpty($this->user->fresh()->projects->where('name', $this->name));

        $users = factory(User::class, 8)->create();
        foreach ($users as $user) {
            $user->companies()->attach($this->company->id);
        }

        $owner_role = Role::findByName(RoleType::OWNER);
        $admin_role = Role::findByName(RoleType::ADMIN);
        $seller_role = Role::findByName(RoleType::DEALER);

        $this->post('/projects/?selected_company_id=' . $this->company->id, [
            'name' => $this->name,
            'short_name' => $this->short_name,
            'users' => [
                [
                    'user_id' => $users[2]->id,
                    'role_id' => $admin_role->id,
                ],
                [
                    'user_id' => $users[1]->id,
                    'role_id' => $owner_role->id,
                ],
                [
                    'user_id' => $users[2]->id,
                    'role_id' => $seller_role->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(
            ['users.0.user_id', 'users.2.user_id'],
            ['name', 'short_name', 'users', 'users.1.user_id']
        );
    }

    /** @test */
    public function store_it_gets_error_when_user_doesnt_belong_to_current_company()
    {
        // check if there are no projects with this name
        $this->assertEmpty($this->user->fresh()->projects->where('name', $this->name));

        $users = factory(User::class, 8)->create();
        $users[0]->companies()->attach($this->company->id);
        $users[2]->companies()->attach($this->company->id);

        $owner_role = Role::findByName(RoleType::OWNER);
        $admin_role = Role::findByName(RoleType::ADMIN);
        $seller_role = Role::findByName(RoleType::DEALER);

        $this->post('/projects/?selected_company_id=' . $this->company->id, [
            'name' => $this->name,
            'short_name' => $this->short_name,
            'users' => [
                [
                    'user_id' => $users[0]->id,
                    'role_id' => $admin_role->id,
                ],
                [
                    'user_id' => $users[1]->id,
                    'role_id' => $owner_role->id,
                ],
                [
                    'user_id' => $users[2]->id,
                    'role_id' => $seller_role->id,
                ],

            ],
        ]);

        $this->verifyValidationResponse(
            ['users.1.user_id'],
            ['name', 'short_name', 'users', 'users.0.user_id', 'users.2.user_id']
        );
    }

    /** @test */
    public function store_it_gets_error_when_invalid_role_is_sent()
    {
        // check if there are no projects with this name
        $this->assertEmpty($this->user->fresh()->projects->where('name', $this->name));

        $developer_role = Role::findByName(RoleType::DEVELOPER);
        $this->company->roles()->detach($developer_role->id);

        $user = factory(User::class)->create();
        $user->companies()->attach($this->company->id);

        $this->post('/projects/?selected_company_id=' . $this->company->id, [
            'name' => $this->name,
            'short_name' => $this->short_name,
            'users' => [
                [
                    'user_id' => $user->id,
                    'role_id' => $developer_role->id,
                ],
            ],
        ]);

        $this->verifyValidationResponse(
            ['users.0.role_id'],
            ['name', 'short_name', 'users', 'users.0.user_id']
        );
    }

    /** @test */
    public function store_it_gets_error_when_no_users_sent()
    {
        $this->post('/projects/?selected_company_id=' . $this->company->id, [
            'name' => $this->name,
            'short_name' => $this->short_name,
            'users' => [],
        ]);

        $this->verifyValidationResponse(['users'], ['name', 'short_name']);
    }

    /** @test */
    public function store_it_creates_project_with_client_role_and_get_401_exception()
    {
        $this->company = $this->createCompanyWithRole(RoleType::CLIENT);

        $projectsCount = Project::all()->count();
        $this->post('/projects/?selected_company_id=' . $this->company->id, [
            'name' => $this->name,
            'short_name' => $this->short_name,
        ]);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
        $this->assertCount($projectsCount, Project::all());
    }

    /** @test */
    public function store_url_with_no_existing_company_id_should_throw_401_exception()
    {
        $this->post('/projects/?selected_company_id=' . ((int) $this->company->id + 1), [
            'name' => $this->name,
            'short_name' => $this->short_name,
        ]);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_url_with_wrong_company_id_should_throw_401_exception()
    {
        $otherCompany = factory(Company::class)->create();

        $this->post('/projects/?selected_company_id=' . $otherCompany->id, [
            'name' => $this->name,
            'short_name' => $this->short_name,
        ]);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function store_send_empty_data_should_throw_validation_error()
    {
        $this->post('/projects/?selected_company_id=' . $this->company->id, [
            'name' => '',
            'short_name' => '',
        ]);
        $this->verifyValidationResponse([
            'name',
            'short_name',
            'first_number_of_tickets',
            'time_tracking_visible_for_clients',
            'language',
            'email_notification_enabled',
            'email_notification_enabled',
            'color',
        ]);
    }
}
