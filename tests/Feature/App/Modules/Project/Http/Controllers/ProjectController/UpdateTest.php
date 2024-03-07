<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController;

use App\Models\Db\Package;
use App\Models\Db\Status;
use Illuminate\Support\Facades\Event;
use App\Models\Db\Role;
use App\Models\Db\Ticket;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Helpers\ErrorCode;
use Carbon\Carbon;
use Tests\BrowserKitTestCase;

class UpdateTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    protected $company;
    protected $new_name;
    protected $new_short_name;
    protected $name;
    protected $short_name;
    protected $time_tracking_visible_for_clients;
    protected $status_for_calendar_id;
    protected $language;
    protected $email_notification_enabled;
    protected $slack_notification_enabled;
    protected $slack_webhook_url;
    protected $slack_channel;
    protected $color;
    protected $ticket_scheduled_dates_with_time;
    protected $developer;
    protected $project;
    protected $now;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->developer = factory(User::class)->create();

        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_FREE);

        // make sure all roles are assigned into this company
        $this->company->roles()->sync(Role::all()->pluck('id'));

        $this->name = 'Test name';
        $this->short_name = 'Test';
        $this->new_name = 'New name';
        $this->new_short_name = 'New';
        $this->time_tracking_visible_for_clients = 1;
        $this->language = 'pl';
        $this->email_notification_enabled = 1;
        $this->slack_notification_enabled = 1;
        $this->slack_webhook_url = 'http://example.pl';
        $this->slack_channel = 'test';
        $this->color = 'test';
        $this->ticket_scheduled_dates_with_time = 1;
        $this->project = factory(Project::class)->create([
            'company_id' => $this->company->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'time_tracking_visible_for_clients' => $this->time_tracking_visible_for_clients,
            'language' => $this->language,
            'ticket_scheduled_dates_with_time' => false,
        ]);
        $this->status_for_calendar_id = factory(Status::class)->create([
            'project_id' => $this->project->id,
            'priority' => 1,
        ])->id;
        $this->project->users()->attach($this->user);
    }

//    public function update_it_updates_project_with_too_many_users()
//    {
//        Event::fake();
//
//        // check projects name and short name
//        $this->assertSame($this->name, $this->project->name);
//        $this->assertSame($this->short_name, $this->project->short_name);
//
//        $users = factory(User::class, 15)->create();
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
//        $this->project->users()->sync([
//            $users[9]->id => ['role_id' => $owner_role->id],
//            $users[10]->id => ['role_id' => $admin_role->id],
//            $users[11]->id => ['role_id' => $seller_role->id],
//            $users[12]->id => ['role_id' => $developer_role->id],
//            $users[13]->id => ['role_id' => $client_role->id],
//        ]);
//
//        $this->assertCount(5, $this->project->users);
//
//        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
//            $this->company->id, [
//            'name' => $this->new_name,
//            'short_name' => $this->new_short_name,
//            'time_tracking_visible_for_clients' => 0,
//            'status_for_calendar_id' => $this->status_for_calendar_id,
//            'language' => 'pl',
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
//        $this->assertCount(5, $this->project->users()->get());
//    }

    /** @test */
    public function update_it_updates_project_with_success()
    {
        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_CLASSIC);
        $this->project = factory(Project::class)->create([
            'company_id' => $this->company->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'time_tracking_visible_for_clients' => $this->time_tracking_visible_for_clients,
            'language' => $this->language,
        ]);
        $this->status_for_calendar_id = factory(Status::class)->create([
            'project_id' => $this->project->id,
            'priority' => 1,
        ])->id;
        $this->project->users()->attach($this->user);

        // make sure all roles are assigned into this company
        $this->company->roles()->sync(Role::all()->pluck('id'));

        Event::fake();

        // check projects name and short name
        $this->assertSame($this->name, $this->project->name);
        $this->assertSame($this->short_name, $this->project->short_name);

        $users = factory(User::class, 15)->create();
        foreach ($users as $user) {
            $user->companies()->attach($this->company->id);
        }

        $owner_role = Role::findByName(RoleType::OWNER);
        $admin_role = Role::findByName(RoleType::ADMIN);
        $seller_role = Role::findByName(RoleType::DEALER);
        $developer_role = Role::findByName(RoleType::DEVELOPER);
        $client_role = Role::findByName(RoleType::CLIENT);

        $this->project->users()->sync([
            $users[9]->id => ['role_id' => $owner_role->id],
            $users[10]->id => ['role_id' => $admin_role->id],
            $users[11]->id => ['role_id' => $seller_role->id],
            $users[12]->id => ['role_id' => $developer_role->id],
            $users[13]->id => ['role_id' => $client_role->id],
        ]);

        $this->assertCount(5, $this->project->users);

        $response = $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
            'time_tracking_visible_for_clients' => 0,
            'status_for_calendar_id' => $this->status_for_calendar_id,
            'language' => 'pl',
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'ticket_scheduled_dates_with_time' => $this->ticket_scheduled_dates_with_time,
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
        ]);

        $response->seeStatusCode(200)->seeJsonContains([
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
            'time_tracking_visible_for_clients' => 0,
            'status_for_calendar_id' => $this->status_for_calendar_id,
            'language' => 'pl',
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'ticket_scheduled_dates_with_time' => $this->ticket_scheduled_dates_with_time,
            'company_id' => $this->company->id,
            'closed_at' => null,
            'deleted_at' => null,
        ])->isJson();

        // Check projects new name and short name
        $updated_project = $this->project->fresh();
        $this->assertSame($this->new_name, $updated_project->name);
        $this->assertSame($this->new_short_name, $updated_project->short_name);
        $this->assertSame(0, $updated_project->time_tracking_visible_for_clients);
        $this->assertSame($this->status_for_calendar_id, $updated_project->status_for_calendar_id);
        $this->assertSame($this->language, $updated_project->language);
        $this->assertSame($this->email_notification_enabled, $updated_project->email_notification_enabled);
        $this->assertSame($this->slack_notification_enabled, $updated_project->slack_notification_enabled);
        $this->assertSame($this->slack_webhook_url, $updated_project->slack_webhook_url);
        $this->assertSame($this->slack_channel, $updated_project->slack_channel);
        $this->assertSame($this->color, $updated_project->color);

        $this->assertCount(7, $updated_project->users);

        $this->assertEquals(
            [$users[2]->id],
            $updated_project->users->where('pivot.role_id', $admin_role->id)->pluck('id')->all()
        );

        $this->assertEquals(
            [$users[1]->id],
            $updated_project->users->where('pivot.role_id', $owner_role->id)->pluck('id')->all()
        );

        $this->assertEquals(
            [$users[0]->id],
            $updated_project->users->where('pivot.role_id', $seller_role->id)->pluck('id')->all()
        );

        $this->assertEquals(
            [$users[4]->id],
            $updated_project->users->where('pivot.role_id', $client_role->id)->pluck('id')->all()
        );

        $this->assertEquals(
            [$users[3]->id, $users[5]->id, $users[6]->id],
            $updated_project->users->where('pivot.role_id', $developer_role->id)->pluck('id')->all()
        );
    }

    /** @test */
    public function update_it_updates_project_with_success_but_doesnt_update_short_name_when_project_has_tickets()
    {
        Event::fake();

        // check projects name and short name
        $this->assertSame($this->name, $this->project->name);
        $this->assertSame($this->short_name, $this->project->short_name);

        $users = factory(User::class, 15)->create();
        foreach ($users as $user) {
            $user->companies()->attach($this->company->id);
        }

        $owner_role = Role::findByName(RoleType::OWNER);
        $admin_role = Role::findByName(RoleType::ADMIN);

        $this->project->users()->sync([
            $users[9]->id => ['role_id' => $owner_role->id],
        ]);

        $this->assertCount(1, $this->project->users);

        // create soft-deleted ticket for project
        $ticket = factory(Ticket::class)->create(['project_id' => $this->project->id]);
        $ticket->delete();

        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
            'time_tracking_visible_for_clients' => 0,
            'status_for_calendar_id' => null,
            'language' => 'pl',
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'ticket_scheduled_dates_with_time' => $this->ticket_scheduled_dates_with_time,
            'users' => [
                [
                    'user_id' => $users[2]->id,
                    'role_id' => $admin_role->id,
                ],
            ],
        ])->seeStatusCode(200)->seeJsonContains([
            'name' => $this->new_name,
            'short_name' => $this->short_name, // here we have old short_name
            'company_id' => $this->company->id,
            'time_tracking_visible_for_clients' => 0,
            'status_for_calendar_id' => null,
            'language' => 'pl',
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'closed_at' => null,
            'deleted_at' => null,
        ])->isJson();

        // Check projects new name and short name
        $updated_project = $this->project->fresh();
        $this->assertSame($this->new_name, $updated_project->name);
        // here we have also old short_name
        $this->assertSame($this->short_name, $updated_project->short_name);
        $this->assertSame(0, $updated_project->time_tracking_visible_for_clients);
        $this->assertSame(null, $updated_project->status_for_calendar_id);
        $this->assertSame($this->language, $updated_project->language);
        $this->assertSame($this->email_notification_enabled, $updated_project->email_notification_enabled);
        $this->assertSame($this->slack_notification_enabled, $updated_project->slack_notification_enabled);
        $this->assertSame($this->slack_webhook_url, $updated_project->slack_webhook_url);
        $this->assertSame($this->slack_channel, $updated_project->slack_channel);
        $this->assertSame($this->color, $updated_project->color);

        $this->assertCount(1, $updated_project->users);

        $this->assertEquals(
            [$users[2]->id],
            $updated_project->users->where('pivot.role_id', $admin_role->id)->pluck('id')->all()
        );
    }

    /** @test */
    public function update_it_gets_error_when_user_is_duplicated()
    {
        // check projects name and short name
        $this->assertSame($this->name, $this->project->name);
        $this->assertSame($this->short_name, $this->project->short_name);

        $users = factory(User::class, 8)->create();
        foreach ($users as $user) {
            $user->companies()->attach($this->company->id);
        }

        $owner_role = Role::findByName(RoleType::OWNER);
        $admin_role = Role::findByName(RoleType::ADMIN);
        $seller_role = Role::findByName(RoleType::DEALER);

        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
            'status_for_calendar_id' => factory(Status::class)->create(['project_id' => 0])->id,
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
            ['users.0.user_id', 'users.2.user_id', 'status_for_calendar_id'],
            ['name', 'short_name', 'users', 'users.1.user_id']
        );

        // Make sure project was not updated
        $updated_project = $this->project->fresh();
        $this->assertSame($this->name, $updated_project->name);
        $this->assertSame($this->short_name, $updated_project->short_name);
    }

    /** @test */
    public function update_it_gets_error_when_user_doesnt_belong_to_current_company()
    {
        $this->markTestSkipped('Disabling validation if user belongs to company ');
        // check projects name and short name
        $this->assertSame($this->name, $this->project->name);
        $this->assertSame($this->short_name, $this->project->short_name);

        $users = factory(User::class, 8)->create();
        $users[0]->companies()->attach($this->company->id);
        $users[2]->companies()->attach($this->company->id);

        $owner_role = Role::findByName(RoleType::OWNER);
        $admin_role = Role::findByName(RoleType::ADMIN);
        $seller_role = Role::findByName(RoleType::DEALER);

        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
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

        // Make sure project was not updated
        $updated_project = $this->project->fresh();
        $this->assertSame($this->name, $updated_project->name);
        $this->assertSame($this->short_name, $updated_project->short_name);
    }

    /** @test */
    public function update_it_gets_error_when_invalid_role_is_sent()
    {
        // check projects name and short name
        $this->assertSame($this->name, $this->project->name);
        $this->assertSame($this->short_name, $this->project->short_name);

        $developer_role = Role::findByName(RoleType::DEVELOPER);
        $this->company->roles()->detach($developer_role->id);

        $user = factory(User::class)->create();
        $user->companies()->attach($this->company->id);

        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
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

        // Make sure project was not updated
        $updated_project = $this->project->fresh();
        $this->assertSame($this->name, $updated_project->name);
        $this->assertSame($this->short_name, $updated_project->short_name);
    }

    /** @test */
    public function update_it_gets_error_when_no_users_sent()
    {
        // check projects name and short name
        $this->assertSame($this->name, $this->project->name);
        $this->assertSame($this->short_name, $this->project->short_name);

        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
            'users' => [],
        ]);

        $this->verifyValidationResponse(['users'], ['name', 'short_name']);

        // Make sure project was not updated
        $updated_project = $this->project->fresh();
        $this->assertSame($this->name, $updated_project->name);
        $this->assertSame($this->short_name, $updated_project->short_name);
    }

    /** @test */
    public function update_it_updates_closed_project_with_success()
    {
        Event::fake();

        // close project
        $this->project->closed_at = $this->now->toDateTimeString();
        $this->project->save();

        // check projects name and short name
        $this->assertSame($this->name, $this->project->name);
        $this->assertSame($this->short_name, $this->project->short_name);

        $users = factory(User::class, 2)->create();
        $users[0]->companies()->attach($this->company->id);
        $users[1]->companies()->attach($this->company->id);

        $owner_role = Role::findByName(RoleType::OWNER);
        $seller_role = Role::findByName(RoleType::DEALER);

        $this->project->users()->sync([$users[0]->id => ['role_id' => $owner_role->id]]);

        $this->assertCount(1, $this->project->users);
        $this->assertEquals(
            [$users[0]->id],
            $this->project->users->where('pivot.role_id', $owner_role->id)->pluck('id')->all()
        );
        $this->assertEquals(
            [],
            $this->project->users->where('pivot.role_id', $seller_role->id)->pluck('id')->all()
        );

        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
            'time_tracking_visible_for_clients' => 0,
            'language' => 'pl',
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'ticket_scheduled_dates_with_time' => $this->ticket_scheduled_dates_with_time,
            'users' => [
                [
                    'user_id' => $users[1]->id,
                    'role_id' => $seller_role->id,
                ],
            ],
        ])->seeStatusCode(200)->seeJsonContains([
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
            'time_tracking_visible_for_clients' => 0,
            'language' => 'pl',
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'company_id' => $this->company->id,
            'closed_at' => $this->now->toDateTimeString(),
            'deleted_at' => null,
        ])->isJson();

        // Check projects new name and short name
        $updated_project = $this->project->fresh();
        $this->assertSame($this->new_name, $updated_project->name);
        $this->assertSame($this->new_short_name, $updated_project->short_name);
        $this->assertSame(0, $updated_project->time_tracking_visible_for_clients);
        $this->assertSame(null, $updated_project->status_for_calendar_id);
        $this->assertSame($this->language, $updated_project->language);
        $this->assertSame($this->email_notification_enabled, $updated_project->email_notification_enabled);
        $this->assertSame($this->slack_notification_enabled, $updated_project->slack_notification_enabled);
        $this->assertSame($this->slack_webhook_url, $updated_project->slack_webhook_url);
        $this->assertSame($this->slack_channel, $updated_project->slack_channel);
        $this->assertSame($this->color, $updated_project->color);

        // verify users
        $this->assertCount(1, $updated_project->users);
        $this->assertEquals(
            [],
            $updated_project->users->where('pivot.role_id', $owner_role->id)->pluck('id')->all()
        );
        $this->assertEquals(
            [$users[1]->id],
            $updated_project->users->where('pivot.role_id', $seller_role->id)->pluck('id')->all()
        );
    }

    /** @test */
    public function update_admin_updates_only_name_and_users_when_short_name_is_not_changed()
    {
        Event::fake();

        // check projects name and short name
        $this->assertSame($this->name, $this->project->name);
        $this->assertSame($this->short_name, $this->project->short_name);

        $users = factory(User::class, 2)->create();
        $users[0]->companies()->attach($this->company->id);
        $users[1]->companies()->attach($this->company->id);

        $owner_role = Role::findByName(RoleType::OWNER);
        $seller_role = Role::findByName(RoleType::DEALER);

        $this->project->users()->sync([$users[0]->id => ['role_id' => $owner_role->id]]);

        $this->assertCount(1, $this->project->users);
        $this->assertEquals(
            [$users[0]->id],
            $this->project->users->where('pivot.role_id', $owner_role->id)->pluck('id')->all()
        );
        $this->assertEquals(
            [],
            $this->project->users->where('pivot.role_id', $seller_role->id)->pluck('id')->all()
        );

        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->short_name,
            'time_tracking_visible_for_clients' => 0,
            'language' => 'pl',
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'ticket_scheduled_dates_with_time' => $this->ticket_scheduled_dates_with_time,
            'users' => [
                [
                    'user_id' => $users[1]->id,
                    'role_id' => $seller_role->id,
                ],
            ],
        ])->seeStatusCode(200)->seeJsonContains([
            'name' => $this->new_name,
            'short_name' => $this->short_name,
            'time_tracking_visible_for_clients' => 0,
            'language' => 'pl',
            'email_notification_enabled' => $this->email_notification_enabled,
            'slack_notification_enabled' => $this->slack_notification_enabled,
            'slack_webhook_url' => $this->slack_webhook_url,
            'slack_channel' => $this->slack_channel,
            'color' => $this->color,
            'company_id' => $this->company->id,
            'closed_at' => null,
            'deleted_at' => null,
        ])->isJson();

        // Check projects new name and old short name
        $updated_project = $this->project->fresh();
        $this->assertSame($this->new_name, $updated_project->name);
        $this->assertSame($this->short_name, $updated_project->short_name);
        $this->assertSame(0, $updated_project->time_tracking_visible_for_clients);
        $this->assertSame(null, $updated_project->status_for_calendar_id);
        $this->assertSame($this->language, $updated_project->language);
        $this->assertSame($this->email_notification_enabled, $updated_project->email_notification_enabled);
        $this->assertSame($this->slack_notification_enabled, $updated_project->slack_notification_enabled);
        $this->assertSame($this->slack_webhook_url, $updated_project->slack_webhook_url);
        $this->assertSame($this->slack_channel, $updated_project->slack_channel);
        $this->assertSame($this->color, $updated_project->color);

        // verify users
        $this->assertCount(1, $updated_project->users);
        $this->assertEquals(
            [],
            $updated_project->users->where('pivot.role_id', $owner_role->id)->pluck('id')->all()
        );
        $this->assertEquals(
            [$users[1]->id],
            $updated_project->users->where('pivot.role_id', $seller_role->id)->pluck('id')->all()
        );
    }

    /** @test */
    public function update_admin_updates_project_from_other_company_get_error()
    {
        // create new company and move project to this company
        $new_company = factory(Company::class)->create();
        $this->project->company_id = $new_company->id;
        $this->project->save();

        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
        ])->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function update_admin_updates_project_with_too_long_short_name_get_error()
    {
        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => 'Too long name for short name',
        ]);
        $this->verifyValidationResponse([
            'short_name',
        ]);
    }

    /** @test */
    public function update_admin_updates_project_with_not_unique_values_get_error()
    {
        // create project with same value that admin will try to update
        factory(Project::class)->create([
            'company_id' => $this->company->id,
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
        ]);

        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
        ]);
        $this->verifyValidationResponse([
            'name',
            'short_name',
        ]);
    }

    /** @test */
    public function update_developer_updates_project_get_error()
    {
        $this->be($this->developer);

        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => $this->new_name,
            'short_name' => $this->new_short_name,
        ])->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function update_admin_updates_project_with_empty_data_get_error()
    {
        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id, [
            'name' => '',
            'short_name' => '',
        ]);
        $this->verifyValidationResponse([
            'name',
            'short_name',
        ]);
    }

    /** @test */
    public function update_admin_updates_project_with_no_data_get_error()
    {
        $this->put('/projects/' . $this->project->id . '/?selected_company_id=' .
            $this->company->id);
        $this->verifyValidationResponse([
            'name',
            'short_name',
            'time_tracking_visible_for_clients',
            'language',
            'email_notification_enabled',
            'slack_notification_enabled',
            'color',
        ]);
    }
}
