<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers\InvitationController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Invitation;
use App\Models\Db\Package;
use App\Models\Other\ModuleType;
use App\Models\Other\InvitationStatus;
use App\Models\Db\Role;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;
use App\Notifications\CreatedNewUser;
use App\Notifications\ExistingUserInvitationCreated;
use App\Notifications\NewUserInvitationCreated;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Config;
use Illuminate\Http\Response;
use Notification;
use App\Modules\User\Events\UserWasAssignedToCompany;
use App\Modules\User\Events\UserWasCreated;
use Event;
use Tests\BrowserKitTestCase;

class StoreTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    public function setUp():void
    {
        parent::setUp();
    }

    /** @test */
    public function it_gets_401_when_developer()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $this->post("companies/{$company->id}/invitations?selected_company_id={$company->id}");

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function it_gets_422_when_admin_with_no_data_with_invitations_enabled()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::ADMIN, UserCompanyStatus::APPROVED, [], Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $otherCompany = factory(Company::class)->create();

        $this->post("companies/{$otherCompany->id}/invitations?selected_company_id={$company->id}");

        $this->verifyValidationResponse([
            'email',
            'role_id',
            'company_id',
            'url',
        ], [
            'first_name',
            'last_name',
            'password',
            'password_confirmation',
            'language',
        ]);
    }

    /** @test */
    public function it_gets_422_when_admin_with_no_data_with_invitations_disabled()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $otherCompany = factory(Company::class)->create();

        // disable invitations
        $this->setAppSettings($company, ModuleType::GENERAL_INVITE_ENABLED, false);

        $this->post("companies/{$otherCompany->id}/invitations?selected_company_id={$company->id}");

        $this->verifyValidationResponse([
            'email',
            'role_id',
            'company_id',
            'password',
        ], [
            'first_name',
            'last_name',
            'url',
            'language',
        ]);
    }

    /** @test */
    public function it_gets_422_when_add_duplicate_email_with_invitations_disabled()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        // disable invitations
        $this->setAppSettings($company, ModuleType::GENERAL_INVITE_ENABLED, false);

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

        $duplicate_user = factory(User::class)->create();
        $post_data = [
            'email' => $duplicate_user->email,
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $this->post(
            "companies/{$company->id}/invitations?selected_company_id={$company->id}",
            $post_data
        )->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'email',
        ], [
            'role_id',
            'company_id',
            'password',
            'first_name',
            'last_name',
            'url',
            'language',
        ]);
    }

    /** @test */
    public function it_gets_422_when_owner_with_invalid_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $this->post("companies/{$company->id}/invitations?selected_company_id={$company->id}", [
            'url' => '',
            'email' => 'sample@example.com',
            'role_id' => Role::findByName(RoleType::OWNER)->id,
        ]);

        $this->verifyValidationResponse([
            'role_id',
            'url',
        ], [
            'first_name',
            'last_name',
            'company_id',
            'email',
            'language',
        ]);
    }

    /** @test */
    public function it_gets_422_when_email_blacklist()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $this->post("companies/{$company->id}/invitations?selected_company_id={$company->id}", [
            'email' => 'test@podam.pl',
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
        ]);

        $this->verifyValidationResponse(['email']);
    }

    /** @test */
    public function it_gets_422_when_admin_is_not_approved_company_admin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::ADMIN, UserCompanyStatus::DELETED, [], Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $this->post("companies/{$company->id}/invitations?selected_company_id={$company->id}", [
            'url' => '',
            'email' => 'sample@example.com',
            'role_id' => Role::findByName(RoleType::OWNER)->id,
        ]);

        // permission is based on user company, but it has to be approved, otherwise it means that
        // user has no role assigned for this company so action cannot be performed
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function it_adds_new_invitation_and_send_valid_notification_when_user_does_not_exist()
    {
        Notification::fake();
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::ADMIN),
                Role::findByName(RoleType::EMPLOYEE),
            ]
            ),
            $company
        );

        $data = [
            'url' => 'http://sample.com/:token/:email/:company',
            'email' => 'sample@example.com',
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'password' => 'password',
            'password_confirmation' => 'password',
            'language' => 'pl',
        ];

        $now = Carbon::parse('2015-01-02 05:13:13');
        Carbon::setTestNow(clone $now);

        $initialUserCount = User::count();

        $this->post(
            "companies/{$company->id}/invitations?selected_company_id={$company->id}",
            $data
        )->seeStatusCode(201);

        // verify whether response is empty
        $response = $this->decodeResponseJson();
        $this->assertEquals([], $response['data']);

        // make sure no user was created
        $this->assertSame($initialUserCount, User::count());
        // make sure there is one invitation only
        $this->assertSame(1, Invitation::count());

        // verify invitation record
        $invitation = Invitation::first();
        $this->verifyInvitationRecord($invitation, $data, $now, $company);

        // verify notification
        Notification::assertSentTo(
            $invitation,
            NewUserInvitationCreated::class,
            function ($notification, $channels) use ($data, $company, $invitation) {
                return $channels === ['mail'] &&
                    $notification->company->id == $company->id &&
                    $notification->url == $data['url'] &&
                    str_contains(
                        $notification->getUrl($invitation),
                        rawurlencode($invitation->unique_hash) . '/' . rawurlencode($data['email']) .
                        '/' . rawurlencode($company->name)
                    );
            }
        );
    }

    /** @test */
    public function it_adds_new_invitation_and_send_valid_notification_when_user_exists()
    {
        Notification::fake();
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $company->name = 'Testing company';
        $company->save();
        auth()->loginUsingId($this->user->id);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::DEVELOPER),
                Role::findByName(RoleType::EMPLOYEE),
            ]
            ),
            $company
        );

        $user = factory(User::class)->create();

        $data = [
            'url' => 'http://sample.com/:token/:email/:company',
            'email' => $user->email,
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $now = Carbon::parse('2015-01-02 05:13:13');
        Carbon::setTestNow(clone $now);

        $initialUserCount = User::count();

        $this->post(
            "companies/{$company->id}/invitations?selected_company_id={$company->id}",
            $data
        )->seeStatusCode(201);

        // verify whether response is empty
        $response = $this->decodeResponseJson();
        $this->assertEquals([], $response['data']);

        // make sure no user was created
        $this->assertSame($initialUserCount, User::count());
        // make sure there is one invitation only
        $this->assertSame(1, Invitation::count());

        // verify invitation record
        $invitation = Invitation::first();
        $this->verifyInvitationRecord($invitation, $data, $now, $company);

        // verify no user company assignment has been created yet
        $this->assertNull($user->companies()->find($company->id));

        // verify notification - we also make sure valid channels were used
        Notification::assertSentTo(
            $user,
            ExistingUserInvitationCreated::class,
            function ($notification, $channels) use ($data, $company, $user, $invitation) {
                return $channels === ['mail'] &&
                    $notification->company->id == $company->id &&
                    $notification->url == $data['url'] &&
                    str_contains(
                        $notification->getUrl($user),
                        rawurlencode($invitation->unique_hash) . '/' . rawurlencode($data['email']) .
                        '/' . 'Testing%20company'
                    ); // here we want to make sure space is converted to %20 do we verify this manually
            }
        );

        Notification::assertNotSentTo($invitation, NewUserInvitationCreated::class);
    }

    /** @test */
    public function it_gets_error_when_try_to_invite_already_assigned_user_with_assigned_status()
    {
        Notification::fake();
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::ADMIN),
                Role::findByName(RoleType::DEVELOPER),
            ]
            ),
            $company
        );

        $user = factory(User::class)->create();

        $user->companies()->attach($company->id, ['status' => UserCompanyStatus::APPROVED]);

        $data = [
            'url' => 'http://sample.com/:token/:email',
            'email' => $user->email,
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $now = Carbon::parse('2015-01-02 05:13:13');
        Carbon::setTestNow(clone $now);

        $initialUserCount = User::count();

        $this->post(
            "companies/{$company->id}/invitations?selected_company_id={$company->id}",
            $data
        );

        $this->verifyErrorResponse(409, ErrorCode::COMPANY_INVITATION_ALREADY_ASSIGNED);

        // make sure no user was created
        $this->assertSame($initialUserCount, User::count());
        // make sure no invitation was created
        $this->assertSame(0, Invitation::count());

        // verify notification - we also make sure valid channels were used
        Notification::assertNotSentTo($user, ExistingUserInvitationCreated::class);
    }

    /** @test */
    public function it_doesnt_get_error_when_try_to_invite_already_assigned_user_with_deleted_status()
    {
        Notification::fake();
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::ADMIN),
                Role::findByName(RoleType::DEVELOPER),
            ]
            ),
            $company
        );

        $user = factory(User::class)->create();

        $user->companies()->attach($company->id, ['status' => UserCompanyStatus::DELETED]);

        $data = [
            'url' => 'http://sample.com/:token/:email/:company',
            'email' => $user->email,
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $now = Carbon::parse('2015-01-02 05:13:13');
        Carbon::setTestNow(clone $now);

        $initialUserCount = User::count();

        $this->post(
            "companies/{$company->id}/invitations?selected_company_id={$company->id}",
            $data
        )->seeStatusCode(201);

        // verify whether response is empty
        $response = $this->decodeResponseJson();
        $this->assertEquals([], $response['data']);

        // make sure no user was created
        $this->assertSame($initialUserCount, User::count());
        // make sure there is one invitation only
        $this->assertSame(1, Invitation::count());

        // verify invitation record
        $invitation = Invitation::first();
        $this->verifyInvitationRecord($invitation, $data, $now, $company);

        // verify no user company assignment has been created yet
        $this->assertEquals(1, $user->companies()->where('company_id', $company->id)->count());

        // verify only previous company assignment was created
        $this->assertEquals(1, $user->companies()->wherePivot('status', UserCompanyStatus::DELETED)
            ->where('company_id', $company->id)->count());

        // verify notification - we also make sure valid channels were used
        Notification::assertSentTo(
            $user,
            ExistingUserInvitationCreated::class,
            function ($notification, $channels) use ($data, $company, $user, $invitation) {
                return $channels === ['mail'] &&
                 $notification->company->id == $company->id &&
                    $notification->url == $data['url'] &&
                    str_contains(
                        $notification->getUrl($user),
                        rawurlencode($invitation->unique_hash) . '/' . rawurlencode($data['email']) .
                        '/' . rawurlencode($company->name)
                    );
            }
        );

        Notification::assertNotSentTo($invitation, NewUserInvitationCreated::class);
    }

    /** @test */
    public function store_user_without_invite_response_structure()
    {
        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        // disable invitations
        $this->setAppSettings($company, ModuleType::GENERAL_INVITE_ENABLED, false);
        auth()->loginUsingId($this->user->id);

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

        $post_data = [
            'email' => 'sample@example.com',
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $this->post(
            "companies/{$company->id}/invitations?selected_company_id={$company->id}",
            $post_data
        )->assertResponseStatus(201)->seeJsonStructure([
            'data' => [
                'id',
                'email',
                'first_name',
                'last_name',
                'avatar',
                'activated',
                'deleted',
            ],
        ]);
    }

    /** @test */
    public function store_user_without_invite_return_response_data()
    {
        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        // disable invitations
        $this->setAppSettings($company, ModuleType::GENERAL_INVITE_ENABLED, false);

        auth()->loginUsingId($this->user->id);

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

        $post_data = [
            'email' => 'sample@example.com',
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $this->post(
            "companies/{$company->id}/invitations?selected_company_id={$company->id}",
            $post_data
        );

        $json = $this->decodeResponseJson()['data'];

        $last_user = User::latest('id')->first();

        $this->assertSame($last_user->id, $json['id']);
        $this->assertSame('sample@example.com', $json['email']);
        $this->assertSame('First name', $json['first_name']);
        $this->assertSame('Last name', $json['last_name']);
        $this->assertSame($last_user->avatar, $json['avatar']);
        $this->assertSame(true, $json['activated']);
        $this->assertSame(false, $json['deleted']);

        Notification::assertSentTo(
            $last_user,
            CreatedNewUser::class,
            function ($notification, $channels) {
                return $channels == ['mail'];
            }
        );
    }

    /** @test */
    public function store_user_without_invite_add_to_base()
    {
        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        // disable invitations
        $this->setAppSettings($company, ModuleType::GENERAL_INVITE_ENABLED, false);
        auth()->loginUsingId($this->user->id);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::ADMIN),
                Role::findByName(RoleType::EMPLOYEE),
            ]
            ),
            $company
        );

        $data = [
            'email' => 'sample@example.com',
            'role_id' => Role::findByName(RoleType::EMPLOYEE)->id,
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $initialUserCount = User::count();

        $this->post(
            "companies/{$company->id}/invitations?selected_company_id={$company->id}",
            $data
        );

        // make sure no user was created
        $this->assertNotSame($initialUserCount, User::count());
        $this->assertSame($initialUserCount + 1, User::count());

        $last_user = User::latest('id')->first();

        $this->assertSame($last_user->email, $data['email']);
        $this->assertSame($last_user->first_name, $data['first_name']);
        $this->assertSame($last_user->last_name, $data['last_name']);

        $userCompany = $last_user->userCompanies()->inCompany($company)->first();
        $this->assertSame($company->id, $userCompany->company_id);
        $this->assertSame(Role::findbyName(RoleType::EMPLOYEE)->id, $userCompany->role_id);
        $this->assertSame(UserCompanyStatus::APPROVED, $userCompany->status);

        Notification::assertSentTo(
            $last_user,
            CreatedNewUser::class,
            function ($notification, $channels) {
                return $channels == ['mail'];
            }
        );
    }

    /** @test */
    public function store_user_without_invite_check_is_events_fired_and_no_new_invitation_create()
    {
        Event::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        // disable invitations
        $this->setAppSettings($company, ModuleType::GENERAL_INVITE_ENABLED, false);
        auth()->loginUsingId($this->user->id);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::ADMIN),
                Role::findByName(RoleType::EMPLOYEE),
            ]
            ),
            $company
        );

        $data = [
            'email' => 'sample@example.com',
            'role_id' => Role::findByName(RoleType::EMPLOYEE)->id,
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'password' => 'password',
            'password_confirmation' => 'password',
            'language' => 'pl',
        ];

        $this->post(
            "companies/{$company->id}/invitations?selected_company_id={$company->id}",
            $data
        );

        $this->assertSame(0, Invitation::count());

        $last_user = User::latest('id')->first();

        // verify UserWasCreated was fired
        Event::assertDispatched(UserWasCreated::class, function ($e) use ($last_user, $data) {
            return $e->user->id == $last_user->id && $e->language == $data['language'];
        });

        // verify UserWasAssignedToCompany was fired
        Event::assertDispatched(
            UserWasAssignedToCompany::class,
            function ($e) use ($last_user, $company) {
                return $e->user->id === $last_user->id && $e->companyId == $company->id;
            }
        );
    }

    /** @test */
    public function store_cant_invite_or_create()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::START);
        $this->setAppSettings($company, ModuleType::GENERAL_INVITE_ENABLED, false);
        auth()->loginUsingId($this->user->id);

        $this->assignUserRolesToCompany(
            collect(
                [
                Role::findByName(RoleType::OWNER),
                Role::findByName(RoleType::ADMIN),
                Role::findByName(RoleType::EMPLOYEE),
            ]
            ),
            $company
        );

        $data = [
            'email' => 'sample@example.com',
            'role_id' => Role::findByName(RoleType::EMPLOYEE)->id,
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $this->assertCount(1, User::all());
        $this->post(
            "companies/{$company->id}/invitations?selected_company_id={$company->id}",
            $data
        );

        $this->verifyErrorResponse(409, ErrorCode::PACKAGE_LIMIT_REACHED);
        $this->assertCount(1, User::all());
    }

    protected function verifyInvitationRecord(
        Invitation $invitation,
        array $data,
        Carbon $now,
        Company $company
    ) {
        $this->assertSame($data['email'], $invitation->email);
        $this->assertSame($this->user->id, $invitation->inviting_user_id);
        $this->assertSame($company->id, $invitation->company_id);
        $this->assertSame((clone $now)->addMinutes(Config::get('app_settings.invitations.expire_time'))
            ->toDateTimeString(), $invitation->expiration_time->toDateTimeString());
        $this->assertSame($data['first_name'], $invitation->first_name);
        $this->assertSame($data['last_name'], $invitation->last_name);
        $this->assertSame($data['role_id'], $invitation->role_id);
        $this->assertSame(InvitationStatus::PENDING, $invitation->status);
        $this->assertNotEmpty($invitation->unique_hash);
    }
}
