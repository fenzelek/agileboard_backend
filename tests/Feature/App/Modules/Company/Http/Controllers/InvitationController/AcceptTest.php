<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers\InvitationController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Invitation;
use App\Models\Db\Package;
use App\Models\Other\InvitationStatus;
use App\Models\Db\Role;
use App\Models\Other\ModuleType;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;
use App\Modules\User\Events\UserWasAssignedToCompany;
use App\Modules\User\Events\UserWasCreated;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Event;
use Tests\BrowserKitTestCase;
use Tests\Helpers\CreateUser;

class AcceptTest extends BrowserKitTestCase
{
    use DatabaseTransactions, CreateUser;

    public function setUp():void
    {
        parent::setUp();
        // disabled to not cause rate limiting error
        $this->withoutMiddleware();
    }

    /** @test */
    public function it_gets_422_when_no_data()
    {
        $this->put('companies/invitations/accept');

        $this->verifyValidationResponse([
            'token',
        ]);
    }

    /** @test */
    public function it_requires_password_when_users_does_not_exists()
    {
        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::PENDING;
        $invitation->email = 'test3232323@example.pl';
        $invitation->save();

        $this->put('companies/invitations/accept', ['token' => $invitation->unique_hash]);

        $this->verifyValidationResponse([
            'password',
        ], [
            'token',
        ]);
    }

    /** @test */
    public function it_gets_409_when_invitation_is_not_pending()
    {
        $user = factory(User::class)->create();

        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::APPROVED;
        $invitation->email = $user->email;
        $invitation->save();

        $this->put(
            'companies/invitations/accept',
            ['token' => $invitation->unique_hash]
        );

        $this->verifyErrorResponse(409, ErrorCode::COMPANY_INVITATION_NOT_PENDING);

        $this->assertSame($invitation->status, $invitation->fresh()->status);
    }

    /** @test */
    public function it_gets_409_when_invitation_is_already_expired()
    {
        $user = factory(User::class)->create();

        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::PENDING;
        $invitation->email = $user->email;
        $invitation->expiration_time = Carbon::now()->subMinute(1);
        $invitation->save();

        $this->put(
            'companies/invitations/accept',
            ['token' => $invitation->unique_hash]
        );

        $this->verifyErrorResponse(409, ErrorCode::COMPANY_INVITATION_EXPIRED);

        $this->assertSame($invitation->status, $invitation->fresh()->status);
    }

    /** @test */
    public function it_gets_409_when_user_already_assigned_to_company()
    {
        $user = factory(User::class)->create();
        $company = factory(Company::class)->create();

        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::PENDING;
        $invitation->expiration_time = Carbon::now()->addMinute(1);
        $invitation->company_id = $company->id;
        $invitation->email = $user->email;
        $invitation->save();

        $user->companies()->attach($company->id);

        $this->put(
            'companies/invitations/accept',
            ['token' => $invitation->unique_hash]
        );

        $this->verifyErrorResponse(409, ErrorCode::COMPANY_INVITATION_ALREADY_ASSIGNED);

        $this->assertSame($invitation->status, $invitation->fresh()->status);
    }

    /** @test */
    public function it_assigns_users_to_company_when_user_exists_and_blockade_company()
    {
        Event::fake();

        $this->createUser(); //other user
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_FREE);
        $this->assignUsersToCompany(factory(User::class, 5)->create(), $company);

        $user = factory(User::class)->create();

        $initialCount = User::count();

        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::PENDING;
        $invitation->expiration_time = Carbon::now()->addMinute(1);
        $invitation->company_id = $company->id;
        $invitation->role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $invitation->email = $user->email;
        $invitation->save();

        $this->put(
            'companies/invitations/accept',
            ['token' => $invitation->unique_hash]
        )->seeStatusCode(200);

        // verify whether response is empty
        $response = $this->decodeResponseJson();
        $this->assertEquals([], $response['data']);

        // verify no users were created
        $this->assertSame($initialCount, User::count());

        // verify no UserWasCreated was fired
        Event::assertNotDispatched(UserWasCreated::class);

        // verify whether user was assigned to company
        $userCompany = $user->companies()->inCompany($company)
            ->withPivot('role_id', 'status')->first();
        $this->assertNotNull($userCompany);
        $this->assertSame($invitation->role_id, $userCompany->pivot->role_id);
        $this->assertSame(UserCompanyStatus::APPROVED, $userCompany->pivot->status);

        // verify UserWasAssignedToCompany was fired
        Event::assertDispatched(UserWasAssignedToCompany::class, function ($e) use ($user, $company) {
            return $e->user->id === $user->id && $e->companyId == $company->id;
        });

        // verify invitation status was changed
        $this->assertSame(InvitationStatus::APPROVED, $invitation->fresh()->status);

        //verify blockaded company
        $this->assertSame(ModuleType::GENERAL_MULTIPLE_USERS, $company->fresh()->blockade_company);
    }

    /** @test */
    public function it_assigns_users_to_company_when_user_does_not_exist()
    {
        Event::fake();

        $password = 'samplePassword';

        $this->createUser(); //other user
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_FREE);

        $initialCount = User::count();

        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::PENDING;
        $invitation->expiration_time = Carbon::now()->addMinute(1);
        $invitation->company_id = $company->id;
        $invitation->role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $invitation->email = 'sample@example.com';
        $invitation->first_name = 'First name';
        $invitation->last_name = 'Last name';
        $invitation->save();

        $this->put(
            'companies/invitations/accept',
            [
                'token' => $invitation->unique_hash,
                'password' => $password,
                'password_confirmation' => $password,
                'language' => 'pl',
            ]
        )->seeStatusCode(200);

        // verify whether response is empty
        $response = $this->decodeResponseJson();
        $this->assertEquals([], $response['data']);

        // verify one user was created
        $this->assertSame($initialCount + 1, User::count());

        // verify user data
        $user = User::latest('id')->first();
        $this->assertSame($invitation->email, $user->email);
        $this->assertSame($invitation->first_name, $user->first_name);
        $this->assertSame($invitation->last_name, $user->last_name);
        $this->assertSame(1, $user->activated);

        // verify user password
        $this->assertTrue(auth()->validate(['email' => $user->email, 'password' => $password]));

        // verify UserWasCreated was fired
        Event::assertDispatched(UserWasCreated::class, function ($e) use ($user) {
            return $e->user->id == $user->id && $e->language === 'pl';
        });

        // verify whether user was assigned to company
        $userCompany = $user->companies()->inCompany($company)
            ->withPivot('role_id', 'status')->first();
        $this->assertNotNull($userCompany);
        $this->assertSame($invitation->role_id, $userCompany->pivot->role_id);
        $this->assertSame(UserCompanyStatus::APPROVED, $userCompany->pivot->status);

        // verify UserWasAssignedToCompany was fired
        Event::assertDispatched(UserWasAssignedToCompany::class, function ($e) use ($user, $company) {
            return $e->user->id === $user->id && $e->companyId == $company->id;
        });

        // verify invitation status was changed
        $this->assertSame(InvitationStatus::APPROVED, $invitation->fresh()->status);

        //verify blockaded company
        $this->assertNull($company->fresh()->blockade_company);
    }

    /** @test */
    public function it_assigns_users_to_company_when_user_exists_and_replaces_old_user_role_and_status()
    {
        Event::fake();

        $this->createUser(); //other user
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_FREE);

        $user = factory(User::class)->create();
        $otherCompany = factory(Company::class)->create();

        $initialCount = User::count();

        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::PENDING;
        $invitation->expiration_time = Carbon::now()->addMinute(1);
        $invitation->company_id = $company->id;
        $invitation->role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $invitation->email = $user->email;
        $invitation->save();

        // we assign user to same company with different role and status
        $user->companies()->attach($company->id, [
            'role_id' => Role::findByName(RoleType::CLIENT)->id,
            'status' => UserCompanyStatus::DELETED,
        ]);

        // and we also assign same user to other company
        $user->companies()->attach($otherCompany->id, [
            'role_id' => Role::findByName(RoleType::CLIENT)->id,
            'status' => UserCompanyStatus::DELETED,
        ]);

        // some pre-request tests
        $this->assertEquals(1, $user->companies()->where('company_id', $company->id)->count());
        $this->assertEquals(1, $user->companies()->where('company_id', $otherCompany->id)->count());

        $this->assertEquals(1, $user->companies()->wherePivot('status', UserCompanyStatus::DELETED)
            ->wherePivot('role_id', Role::findByName(RoleType::CLIENT)->id)
            ->where('company_id', $company->id)->count());
        $this->assertEquals(1, $user->companies()->wherePivot('status', UserCompanyStatus::DELETED)
            ->wherePivot('role_id', Role::findByName(RoleType::CLIENT)->id)
            ->where('company_id', $otherCompany->id)->count());

        $this->put(
            'companies/invitations/accept',
            ['token' => $invitation->unique_hash]
        )->seeStatusCode(200);

        // verify whether response is empty
        $response = $this->decodeResponseJson();
        $this->assertEquals([], $response['data']);

        // verify no users were created
        $this->assertSame($initialCount, User::count());

        // verify no UserWasCreated was fired
        Event::assertNotDispatched(UserWasCreated::class);

        // now make sure old entry was replaced with new one, but other company was not touched
        $this->assertEquals(1, $user->companies()->where('company_id', $company->id)->count());
        $this->assertEquals(1, $user->companies()->where('company_id', $otherCompany->id)->count());

        $this->assertEquals(1, $user->companies()->wherePivot('status', UserCompanyStatus::APPROVED)
            ->wherePivot('role_id', Role::findByName(RoleType::DEVELOPER)->id)
            ->where('company_id', $company->id)->count());
        $this->assertEquals(1, $user->companies()->wherePivot('status', UserCompanyStatus::DELETED)
            ->wherePivot('role_id', Role::findByName(RoleType::CLIENT)->id)
            ->where('company_id', $otherCompany->id)->count());

        // verify UserWasAssignedToCompany was fired
        Event::assertDispatched(UserWasAssignedToCompany::class, function ($e) use ($user, $company) {
            return $e->user->id === $user->id && $e->companyId == $company->id;
        });

        // verify invitation status was changed
        $this->assertSame(InvitationStatus::APPROVED, $invitation->fresh()->status);

        //verify blockaded company
        $this->assertNull($company->fresh()->blockade_company);
    }

    /** @test */
    public function it_assigns_users_to_company_when_user_exists_and_replaces_old_user_role_and_status_and_email_in_other_case()
    {
        Event::fake();
        $other_case_email = 'sAmPlE@ExAmPLe.com';
        $original_email = 'sample@example.com';

        $this->createUser(); //other user
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::CEP_FREE);

        $user = factory(User::class)->create(['email' => $original_email]);
        $otherCompany = factory(Company::class)->create();

        $initialCount = User::count();

        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::PENDING;
        $invitation->expiration_time = Carbon::now()->addMinute(1);
        $invitation->company_id = $company->id;
        $invitation->role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $invitation->email = $other_case_email;
        $invitation->save();

        // we assign user to same company with different role and status
        $user->companies()->attach($company->id, [
            'role_id' => Role::findByName(RoleType::CLIENT)->id,
            'status' => UserCompanyStatus::DELETED,
        ]);

        // and we also assign same user to other company
        $user->companies()->attach($otherCompany->id, [
            'role_id' => Role::findByName(RoleType::CLIENT)->id,
            'status' => UserCompanyStatus::DELETED,
        ]);

        // some pre-request tests
        $this->assertEquals(1, $user->companies()->where('company_id', $company->id)->count());
        $this->assertEquals(1, $user->companies()->where('company_id', $otherCompany->id)->count());

        $this->assertEquals(1, $user->companies()->wherePivot('status', UserCompanyStatus::DELETED)
            ->wherePivot('role_id', Role::findByName(RoleType::CLIENT)->id)
            ->where('company_id', $company->id)->count());
        $this->assertEquals(1, $user->companies()->wherePivot('status', UserCompanyStatus::DELETED)
            ->wherePivot('role_id', Role::findByName(RoleType::CLIENT)->id)
            ->where('company_id', $otherCompany->id)->count());

        $this->put(
            'companies/invitations/accept',
            ['token' => $invitation->unique_hash]
        )->seeStatusCode(200);

        // verify whether response is empty
        $response = $this->decodeResponseJson();
        $this->assertEquals([], $response['data']);

        // verify no users were created
        $this->assertSame($initialCount, User::count());

        // verify no UserWasCreated was fired
        Event::assertNotDispatched(UserWasCreated::class);

        // now make sure old entry was replaced with new one, but other company was not touched
        $this->assertEquals(1, $user->companies()->where('company_id', $company->id)->count());
        $this->assertEquals(1, $user->companies()->where('company_id', $otherCompany->id)->count());

        $this->assertEquals(1, $user->companies()->wherePivot('status', UserCompanyStatus::APPROVED)
            ->wherePivot('role_id', Role::findByName(RoleType::DEVELOPER)->id)
            ->where('company_id', $company->id)->count());
        $this->assertEquals(1, $user->companies()->wherePivot('status', UserCompanyStatus::DELETED)
            ->wherePivot('role_id', Role::findByName(RoleType::CLIENT)->id)
            ->where('company_id', $otherCompany->id)->count());

        // verify UserWasAssignedToCompany was fired
        Event::assertDispatched(UserWasAssignedToCompany::class, function ($e) use ($user, $company) {
            return $e->user->id === $user->id && $e->companyId == $company->id;
        });

        // verify invitation status was changed
        $this->assertSame(InvitationStatus::APPROVED, $invitation->fresh()->status);

        //verify blockaded company
        $this->assertNull($company->fresh()->blockade_company);
    }
}
