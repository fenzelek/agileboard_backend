<?php

namespace Tests\Feature\App\Modules\User\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Db\Project;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use App\Modules\User\Events\UserWasCreated;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Event;
use Tests\BrowserKitTestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Mockery;
use Tests\Helpers\ProjectHelper;
use App\Modules\User\Services\Storage as ServicesStorage;
use App\Models\Filesystem\Store as ModelStore;

class UserControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    use ProjectHelper;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var
     */
    protected $avatar_name;

    /**
     * @var Carbon
     */
    protected $now;

    public function setUp():void
    {
        parent::setUp();

        $this->now = Carbon::parse('2017-04-28 12:00:00');
        Carbon::setTestNow($this->now);
    }

    protected function tearDown():void
    {
        if (file_exists(storage_path('phpunit_tests/test/'))) {
            array_map('unlink', glob(storage_path('phpunit_tests/test/*')));
            rmdir(storage_path('phpunit_tests/test/'));
        }

        if (file_exists(storage_path('user/'))) {
            array_map('unlink', glob(storage_path('user/*')));
            rmdir(storage_path('user/'));
        }

        parent::tearDown();
    }

    public function testIndex_whenNotAssignedToCompany()
    {
        $this->createUser();
        $company = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $this->get('/users?selected_company_id=' . $company->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    public function testIndex_whenAdminAndTriesToDisplayOtherCompanyUsers()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);

        $otherCompany = factory(Company::class)->create();

        $this->get('/users?selected_company_id=' . $otherCompany->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    public function testIndex_whenOwner()
    {
        $this->verifyForOwnerOrAdmin(RoleType::OWNER);
    }

    public function testIndex_whenAdmin()
    {
        $this->verifyForOwnerOrAdmin(RoleType::ADMIN);
    }

    public function testIndex_whenSystemAdmin()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);

        $company = factory(Company::class)->create();

        $newUsers = factory(User::class, 7)->create();
        $this->assignUsersToCompany($newUsers, $company, RoleType::DEVELOPER);

        $this->get('/users?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        // get expected users from database
        $users = User::whereIn('id', $newUsers->pluck('id')->all())->orderBy('id')->get();

        // make sure in response we have all users
        $json = $this->decodeResponseJson();
        $responseUsers = $json['data'];
        $this->assertEquals($users->count(), count($responseUsers));
        $this->assertEquals($this->formatUsers($users), $responseUsers);
    }

    public function testIndex_whenDeveloperWithoutProjects()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $this->get('/users?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        // get expected users from database
        $users = User::where('id', $this->user->id)->orderBy('id')->get();

        // make sure in response we have only current user
        $json = $this->decodeResponseJson();
        $responseUsers = $json['data'];
        $this->assertEquals(1, count($responseUsers));
        $this->assertEquals($this->formatUsers($users), $responseUsers);
    }

    public function testIndex_whenDeveloperWithProjects()
    {
        $this->verifyForOrdinaryRole(RoleType::DEVELOPER);
    }

    public function testIndex_whenDealerWithProjects()
    {
        $this->verifyForOrdinaryRole(RoleType::DEALER);
    }

    public function testIndex_whenClient()
    {
        // @todo this probably should be also allowed for client but now it's not
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::CLIENT, Package::PREMIUM);

        $otherCompany = factory(Company::class)->create();

        $this->get('/users?selected_company_id=' . $otherCompany->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    public function testStoreUser_whenNoData()
    {
        $this->post('/users');

        $this->verifyValidationResponse(['email', 'password', 'first_name', 'last_name', 'url']);
    }

    public function testStoreUser_withData()
    {
        Event::fake();

        $data = factory(User::class)->make()->toArray();
        $data['password'] = 'xxx22c';
        $data['password_confirmation'] = $data['password'];
        $data['url'] = 'http://example.com/:token';
        $data['language'] = 'pl';

        $this->post('/users', $data)->seeStatusCode(201);

        // make sure in response we have valid user data
        $json = $this->decodeResponseJson();
        $responseUser = $json['data'];

        $this->assertEquals([
            'id' => $responseUser['id'],
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'avatar' => '',
            'activated' => false,
            'deleted' => false,
        ], $responseUser);

        // db verification
        $dbUser = User::find($responseUser['id']);
        $this->assertEquals($data['email'], $dbUser->email);
        $this->assertEquals($data['first_name'], $dbUser->first_name);
        $this->assertEquals($data['last_name'], $dbUser->last_name);
        $this->assertEquals('', $dbUser->avatar);
        $this->assertNull($dbUser->discount_code);
        $this->assertEquals(0, $dbUser->activated);
        $this->assertEquals(0, $dbUser->deleted);
        $this->assertTrue(starts_with($dbUser->activate_hash, $responseUser['id'] . '_'));

        // verify whether use can log in with his password
        $this->assertTrue(auth()->validate([
            'email' => $data['email'],
            'password' => $data['password'],
        ]));

        Event::assertDispatched(UserWasCreated::class, function ($e) use ($dbUser, $data) {
            return $e->user->id === $dbUser->id && $e->language === $data['language'] &&
                $e->url = str_replace(':token', $dbUser->activate_hash, $data['url']);
        });
    }

    public function testStoreUser_withDiscountCode()
    {
        Event::fake();

        $data = factory(User::class)->make()->toArray();
        $data['password'] = 'xxx22c';
        $data['password_confirmation'] = $data['password'];
        $data['discount_code'] = 'asdasd';
        $data['url'] = 'http://example.com/:token';
        $data['language'] = 'pl';

        $this->post('/users', $data)->seeStatusCode(201);

        // make sure in response we have valid user data
        $json = $this->decodeResponseJson();
        $responseUser = $json['data'];

        $this->assertEquals([
            'id' => $responseUser['id'],
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'avatar' => '',
            'activated' => false,
            'deleted' => false,
        ], $responseUser);

        // db verification
        $dbUser = User::find($responseUser['id']);
        $this->assertEquals($data['email'], $dbUser->email);
        $this->assertEquals($data['first_name'], $dbUser->first_name);
        $this->assertEquals($data['last_name'], $dbUser->last_name);
        $this->assertEquals('', $dbUser->avatar);
        $this->assertEquals('asdasd', $dbUser->discount_code);
        $this->assertEquals(0, $dbUser->activated);
        $this->assertEquals(0, $dbUser->deleted);
        $this->assertTrue(starts_with($dbUser->activate_hash, $responseUser['id'] . '_'));

        // verify whether use can log in with his password
        $this->assertTrue(auth()->validate([
            'email' => $data['email'],
            'password' => $data['password'],
        ]));

        Event::assertDispatched(UserWasCreated::class, function ($e) use ($dbUser, $data) {
            return $e->user->id === $dbUser->id && $e->language === $data['language'] &&
                $e->url = str_replace(':token', $dbUser->activate_hash, $data['url']);
        });
    }

    public function testStoreUser_WithInvalidData()
    {
        $data = factory(User::class)->make()->toArray();
        $data['password'] = '';
        $data['first_name'] = '';
        $data['last_name'] = '';
        $data['role_id'] = 1;
        $data['password_confirmation'] = $data['password'];
        $data['send_user_notification'] = true;

        $this->post('/users', $data);

        $this->verifyValidationResponse([
            'password',
            'first_name',
            'last_name',
            'url',
        ], [
            'password_confirmation',
            'language',
            'discount_code',
        ]);
    }

    public function testStoreUser_emailBlacklist()
    {
        $data = factory(User::class)->make()->toArray();
        $data['role_id'] = 1;
        $data['email'] = 'test@podam.pl';
        $data['send_user_notification'] = true;

        $this->post('/users', $data);

        $this->verifyValidationResponse(['email']);
    }

    public function testCurrent_whenLogged_AndStandardUser()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->get('/users/current')
            ->seeStatusCode(200)
            ->isJson();

        $json = $this->decodeResponseJson();
        $responseUser = $json['data'];

        $expectedData = $this->formatUser(User::find($this->user->id));
        $expectedData['role'] = RoleType::SYSTEM_USER;
        $expectedData['selected_user_company']['data'] = null;

        $this->assertEquals($expectedData, $responseUser);
    }

    public function testCurrent_whenLogged_AndSuperAdminUser()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);

        $this->get('/users/current')
            ->seeStatusCode(200)
            ->isJson();

        $json = $this->decodeResponseJson();
        $responseUser = $json['data'];

        $expectedData = $this->formatUser(User::find($this->user->id));
        $expectedData['role'] = RoleType::SYSTEM_ADMIN;
        $expectedData['selected_user_company']['data'] = null;

        $this->assertEquals($expectedData, $responseUser);
    }

    public function testCurrent_whenLogged_AndSelectedCompany()
    {
        $extras = [
            'title' => 'Some title',
            'skills' => 'Very talented developer',
            'description' => 'Super great developer',
        ];

        $this->createUser();
        $company = $this->createCompanyWithRole(
            RoleType::DEVELOPER,
            UserCompanyStatus::APPROVED,
            $extras,
            Package::PREMIUM
        );
        auth()->loginUsingId($this->user->id);

        $this->get('/users/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $json = $this->decodeResponseJson();
        $responseUser = $json['data'];

        $expectedData = $this->formatUser(User::find($this->user->id));
        $expectedData['role'] = RoleType::SYSTEM_USER;
        $expectedData['selected_user_company']['data'] = [
            'role' => ['data' => Role::findByName(RoleType::DEVELOPER)->toArray()],
            'company' => [
                'data' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'vat_payer' => true,
                ],
            ],
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
            'title' => $extras['title'],
            'skills' => $extras['skills'],
            'description' => $extras['description'],
            'status' => UserCompanyStatus::APPROVED,
            'department' => null,
            'contract_type' => null,
        ];

        $this->assertEquals($expectedData, $responseUser);
    }

    public function testCurrent_whenNotLogged()
    {
        $this->get('/users/current')->isJson();
        $this->verifyErrorResponse(401);
    }

    public function testCompanies_whenLogged_HasCompany_CheckStructure()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /*
         * Assign company to current user.
         */
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);

        $this->get('/users/current/companies')
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [['id', 'name', 'role', 'owner', 'vatin_prefix', 'enabled']],
            ])
            ->isJson();
    }

    public function testCompanies_whenLogged_HasCompany_CheckData()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        $company->country_vatin_prefix_id = 1;
        $company->save();

        $this->get('/users/current/companies')
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->response->getData()->data[0];

        $this->assertEquals($company->name, $response->name);
        $this->assertEquals($company->id, $response->id);
        $this->assertEquals(1, $response->enabled);
        $this->assertEquals(
            Role::findByName(RoleType::DEVELOPER)->toArray(),
            (array) $response->role->data
        );
        $this->assertEquals('Afganistan', $response->vatin_prefix->data->name);
    }

    public function testCompanies_whenLogged_HasCompanyWithBlockade()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);
        $company->country_vatin_prefix_id = 1;
        $company->blockade_company = 'test';
        $company->save();

        $this->get('/users/current/companies')
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->response->getData()->data[0];

        $this->assertEquals($company->name, $response->name);
        $this->assertEquals($company->id, $response->id);
        $this->assertEquals(0, $response->enabled);
        $this->assertEquals(
            Role::findByName(RoleType::DEVELOPER)->toArray(),
            (array) $response->role->data
        );
        $this->assertEquals('Afganistan', $response->vatin_prefix->data->name);
    }

    /** @test */
    public function companies_test_getting_owner_data()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $users = factory(User::class, 3)->create();
        $this->assignUsersToCompany($users, $company);

        $this->get('/users/current/companies')
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->response->getData()->data[0];

        $this->assertEquals($company->name, $response->name);
        $this->assertEquals($company->id, $response->id);
        $this->assertEquals($company->vatin, $response->vatin);
        $this->assertEquals(1, $response->enabled);
        $this->assertEquals(
            Role::findByName(RoleType::OWNER)->toArray(),
            (array) $response->role->data
        );

        $owner = $response->owner->data;
        $this->assertEquals($this->user->id, $owner->id);
        $this->assertEquals($this->user->email, $owner->email);
        $this->assertEquals($this->user->first_name, $owner->first_name);
        $this->assertEquals($this->user->last_name, $owner->last_name);
        $owner_role_id = User::find($owner->id)->userCompanies->first()->role_id;
        $this->assertEquals(Role::findByName(RoleType::OWNER)->id, $owner_role_id);
    }

    /** @test */
    public function test_companies_get_only_companies_where_user_is_approved()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        // Company::truncate();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $this->createCompanyWithRole(RoleType::OWNER);
        $this->createCompanyWithRole(RoleType::OWNER, UserCompanyStatus::REFUSED);
        $this->createCompanyWithRole(RoleType::OWNER, UserCompanyStatus::DELETED);
        $this->createCompanyWithRole(RoleType::OWNER, UserCompanyStatus::SUSPENDED);

        $this->assertCount(5, Company::all());

        $this->get('/users/current/companies')
            ->seeStatusCode(200)
            ->isJson();

        $this->assertCount(2, $this->response->getData()->data);
    }

    public function testCompanies_whenLogged_HasNotCompany_CheckData()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->get('/users/current/companies')
            ->seeStatusCode(200)
            ->seeJsonStructure([])
            ->isJson();
    }

    public function testCompanies_whenNotLogged()
    {
        $this->get('/users/current/companies')->isJson();
        $this->verifyErrorResponse(401);
    }

    /** @test */
    public function update_super_admin_has_permission()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);
        $regular_users = factory(User::class, 2)->create([
            'first_name' => 'Firstnametest',
            'last_name' => 'Lastnametest',
        ]);

        $this->put('users/' . $regular_users[0]->id, [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'password' => 'simple password',
            'password_confirmation' => 'simple password',
            'avatar' => '',
            'remove_avatar' => 0,
        ])->assertResponseOk();
    }

    /** @test */
    public function update_regular_user_has_permission_yourself()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->put('users/' . $this->user->id, [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'password' => 'simple password',
            'password_confirmation' => 'simple password',
            'old_password' => 'testpassword',
            'avatar' => '',
            'remove_avatar' => 0,
        ])->assertResponseOk();
    }

    /**
     * Test check that regular user can't update data of other user.
     *
     * @test
     */
    public function update_regular_user_has_no_permission_for_other()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $regular_user = factory(User::class)->create();

        $this->put('users/' . $regular_user->id)
            ->assertResponseStatus(401);
    }

    /** @test */
    public function update_validate_password_confirmation_for_super_user()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);

        $regular_user = factory(User::class)->create([
            'first_name' => 'Firstnametest',
            'last_name' => 'Lastnametest',
        ]);

        $password = 'simple password';
        $password_confirmation = 'wrong password';

        $this->put('users/' . $regular_user->id, [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'password' => $password,
            'password_confirmation' => $password_confirmation,
            'avatar' => '',
            'remove_avatar' => 0,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'password',
        ]);

        $json = $this->decodeResponseJson();

        $this->assertSame('Potwierdzenie pola nie zgadza się.', $json['fields']['password'][0]);
    }

    /** @test */
    public function update_validate_password_length_for_super_user()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);

        $regular_user = factory(User::class)->create([
            'first_name' => 'Firstnametest',
            'last_name' => 'Lastnametest',
        ]);

        $password = 'short';
        $password_confirmation = 'short';

        $this->put('users/' . $regular_user->id, [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'password' => $password,
            'password_confirmation' => $password_confirmation,
            'avatar' => '',
            'remove_avatar' => 0,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'password',
        ]);

        $json = $this->decodeResponseJson();

        $this->assertSame('Pole musi mieć przynajmniej 6 znaków.', $json['fields']['password'][0]);
    }

    /** @test */
    public function update_validate_old_password_for_regular_user()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->put('users/' . $this->user->id, [
            'password' => 'test',
        ])
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'old_password',
        ]);
    }

    /** @test */
    public function update_regular_user_send_wrong_old_password()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->put('users/' . $this->user->id, [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'password' => 'simple password',
            'password_confirmation' => 'simple password',
            'old_password' => 'wrong_password',
            'avatar' => '',
            'remove_avatar' => 0,
        ])->assertResponseStatus(422);

        $json = $this->decodeResponseJson();

        $this->assertSame(ErrorCode::PASSWORD_INVALID_PASSWORD, $json['code']);
    }

    /** @test */
    public function update_by_regular_user_storing_data_in_db()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $new_password = 'new_password';
        $old_password = 'testpassword';
        $this->put('users/' . $this->user->id, [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'password' => $new_password,
            'password_confirmation' => $new_password,
            'old_password' => $old_password,
            'avatar' => '',
            'remove_avatar' => 0,
        ])->assertResponseOk();

        $user_fresh = $this->user->fresh();

        $this->assertSame('Jan', $user_fresh->first_name);
        $this->assertSame('Kowalski', $user_fresh->last_name);

        $this->assertTrue(auth()->validate([
            'email' => $this->user->email,
            'password' => $new_password,
        ]));

        $this->assertFalse(auth()->validate([
            'email' => $this->user->email,
            'password' => $old_password,
        ]));
    }

    /** @test */
    public function update_by_super_user_storing_data_in_db()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);

        $new_password = 'new_password';
        $old_password = 'old_password';

        $regular_user = factory(User::class)->create([
            'first_name' => 'Firstnametest',
            'last_name' => 'Lastnametest',
        ]);
        $regular_user->password = $old_password;

        $this->put('users/' . $regular_user->id, [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'password' => $new_password,
            'password_confirmation' => $new_password,
            'old_password' => $old_password,
            'avatar' => '',
            'remove_avatar' => 0,
        ])->assertResponseOk();

        $user_fresh = $regular_user->fresh();

        $this->assertSame('Jan', $user_fresh->first_name);
        $this->assertSame('Kowalski', $user_fresh->last_name);

        $this->assertTrue(auth()->validate([
            'email' => $regular_user->email,
            'password' => $new_password,
        ]));

        $this->assertFalse(auth()->validate([
            'email' => $regular_user->email,
            'password' => $old_password,
        ]));
    }

    /** @test */
    public function update_password_by_super_user_storing_data_in_db()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);

        $new_password = 'new_password';
        $old_password = 'old_password';

        $regular_user = factory(User::class)->create([
            'first_name' => 'Firstnametest',
            'last_name' => 'Lastnametest',
        ]);
        $regular_user->password = $old_password;

        $this->put('users/' . $regular_user->id, [
            'first_name' => 'Firstnametest',
            'last_name' => 'Lastnametest',
            'password' => $new_password,
            'password_confirmation' => $new_password,
            'old_password' => $old_password,
            'avatar' => '',
            'remove_avatar' => 0,
        ])->assertResponseOk();

        $user_fresh = $regular_user->fresh();

        $this->assertSame('Firstnametest', $user_fresh->first_name);
        $this->assertSame('Lastnametest', $user_fresh->last_name);

        $this->assertTrue(auth()->validate([
            'email' => $regular_user->email,
            'password' => $new_password,
        ]));

        $this->assertFalse(auth()->validate([
            'email' => $regular_user->email,
            'password' => $old_password,
        ]));
    }

    /** @test */
    public function update_password_without_confirm_by_super_user()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);

        $new_password = 'new_password';
        $old_password = 'old_password';

        $regular_user = factory(User::class)->create([
            'first_name' => 'Firstnametest',
            'last_name' => 'Lastnametest',
        ]);
        $regular_user->password = $old_password;

        $this->put('users/' . $regular_user->id, [
            'password' => $new_password,
        ])->assertResponseStatus(422);
    }

    /** @test */
    public function update_full_name_by_super_user_storing_data_in_db()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);

        $new_password = 'new_password';
        $old_password = 'old_password';

        $regular_user = factory(User::class)->create([
            'first_name' => 'Firstnametest',
            'last_name' => 'Lastnametest',
            'password' => $old_password,
        ]);

        $this->put('users/' . $regular_user->id, [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => '',
            'remove_avatar' => 0,
        ])->assertResponseOk();

        $user_fresh = $regular_user->fresh();

        $this->assertSame('Jan', $user_fresh->first_name);
        $this->assertSame('Kowalski', $user_fresh->last_name);

        $this->assertTrue(auth()->validate([
            'email' => $regular_user->email,
            'password' => $old_password,
        ]));

        $this->assertFalse(auth()->validate([
            'email' => $regular_user->email,
            'password' => $new_password,
        ]));
    }

    /** @test */
    public function update_full_name_by_user_storing_data_in_db()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $new_password = 'new_password';
        $old_password = 'testpassword';
        $this->put('users/' . $this->user->id, [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => '',
            'remove_avatar' => 0,
        ])->assertResponseOk();

        $user_fresh = $this->user->fresh();

        $this->assertSame('Jan', $user_fresh->first_name);
        $this->assertSame('Kowalski', $user_fresh->last_name);

        $this->assertTrue(auth()->validate([
            'email' => $this->user->email,
            'password' => $old_password,
        ]));

        $this->assertFalse(auth()->validate([
            'email' => $this->user->email,
            'password' => $new_password,
        ]));
    }

    /** @test */
    public function update_response_structure()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $new_password = 'new_password';
        $old_password = 'testpassword';
        $this->put('users/' . $this->user->id, [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'password' => $new_password,
            'password_confirmation' => $new_password,
            'old_password' => $old_password,
            'avatar' => '',
            'remove_avatar' => 0,
        ])
            ->assertResponseOk()
            ->seeJsonStructure([
                'data',
                'exec_time',
            ]);
    }

    /** @test */
    public function update_transaction_rollback_delete_avatar()
    {
        /* **************** setup environments  ********************/
        $now = Carbon::parse('2017-04-28 12:00:00');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /* **************** create mocks  ********************/
        $services_store =
            Mockery::mock(\Illuminate\Filesystem\FilesystemManager::class)->makePartial();
        $services_store->shouldReceive('disk->delete')
            ->once()
            ->andThrow(new \Exception('Failed to delete avatar'));

        app()->instance(\Illuminate\Filesystem\FilesystemManager::class, $services_store);

        /*** prepare data ***/
        $avatar = $this->getAvatar();

        // save user avatar
        $this->user->avatar = $this->avatar_name;
        $this->user->save();

        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => $avatar,
            'remove_avatar' => 1,
        ];

        $realFile = storage_path('phpunit_tests/test/phpunit_avatar.jpg');
        $uploadedLocation = storage_path('user/' . $this->avatar_name);
        copy($realFile, $uploadedLocation);

        // expected data
        $first_name = $this->user->first_name;
        $last_name = $this->user->last_name;

        /* **************** send request  ********************/
        $this->put('users/' . $this->user->id, $data)
            ->verifyErrorResponse(500, ErrorCode::API_ERROR);

        /** @var User $user_fresh */
        $user_fresh = $this->user->fresh();

        $this->assertEquals($first_name, $user_fresh->first_name);
        $this->assertEquals($last_name, $user_fresh->last_name);
        $this->assertEquals($this->avatar_name, $user_fresh->avatar);
        $this->assertTrue(Storage::disk('avatar')->exists($this->avatar_name));
    }

    /** @test */
    public function update_transaction_rollback_update_avatar_user_does_not_have_set_avatar()
    {
        /* **************** setup environments  ********************/
        $now = Carbon::parse('2017-04-28 12:00:00');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /* **************** create mocks  ********************/
        $model_store = Mockery::mock(ModelStore::class)->makePartial();
        $model_store->shouldReceive('fileExists')
            ->once()
            ->andThrow(new \Exception('Failed to save avatar'));

        app()->instance(ModelStore::class, $model_store);

        /*** prepare data ***/
        $avatar = $this->getAvatar();
        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => $avatar,
            'remove_avatar' => 0,
        ];

        // expected data
        $first_name = $this->user->first_name;
        $last_name = $this->user->last_name;

        /* **************** send request  ********************/
        $this->put('users/' . $this->user->id, $data)
            ->verifyErrorResponse(500, ErrorCode::API_ERROR);

        /** @var User $user_fresh */
        $user_fresh = $this->user->fresh();

        $this->assertEquals($first_name, $user_fresh->first_name);
        $this->assertEquals($last_name, $user_fresh->last_name);
        $this->assertEquals(null, $user_fresh->avatar);
        $this->assertFalse(Storage::disk('avatar')->exists($this->avatar_name));
    }

    /** @test */
    public function update_transaction_rollback_update_avatar_user_has_set_avatar()
    {
        /* **************** setup environments  ********************/
        $now = Carbon::parse('2017-04-28 12:00:00');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /* **************** create mocks  ********************/
        $services_store = Mockery::mock(ServicesStorage::class)->makePartial();
        $services_store->shouldReceive('updateAvatar')
            ->once()
            ->andThrow(new \Exception('The name generation limit was exceeded'));

        app()->instance(ServicesStorage::class, $services_store);

        /*** prepare data ***/
        $avatar = $this->getAvatar();

        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => $avatar,
            'remove_avatar' => 0,
        ];

        // expected data
        $first_name = $this->user->first_name;
        $last_name = $this->user->last_name;

        /* **************** send request  ********************/
        $this->put('users/' . $this->user->id, $data)
            ->verifyErrorResponse(500, ErrorCode::API_ERROR);

        /** @var User $user_fresh */
        $user_fresh = $this->user->fresh();

        $this->assertEquals($first_name, $user_fresh->first_name);
        $this->assertEquals($last_name, $user_fresh->last_name);
        $this->assertEquals(null, $user_fresh->avatar);

        $this->assertFalse(Storage::disk('avatar')->exists($this->avatar_name));
    }

    /** @test */
    public function update_invalid_unique_name_return_exception()
    {
        /* **************** setup environments  ********************/
        $now = Carbon::parse('2017-04-28 12:00:00');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /*** prepare data ***/
        $avatar = $this->getAvatar();

        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => $avatar,
            'remove_avatar' => 0,
        ];

        $realFile = storage_path('phpunit_tests/test/phpunit_avatar.jpg');
        $uploadedLocation = storage_path('user/' . $this->avatar_name);
        copy($realFile, $uploadedLocation);

        // expected data
        $first_name = $this->user->first_name;
        $last_name = $this->user->last_name;

        /* **************** send request  ********************/
        $this->put('users/' . $this->user->id, $data)
            ->verifyErrorResponse(500, ErrorCode::API_ERROR);

        /** @var User $user_fresh */
        $user_fresh = $this->user->fresh();

        $this->assertEquals($first_name, $user_fresh->first_name);
        $this->assertEquals($last_name, $user_fresh->last_name);
        $this->assertEquals(null, $user_fresh->avatar);
        $this->assertTrue(Storage::disk('avatar')->exists($this->avatar_name));
    }

    /** @test */
    public function update_avatar_by_regular_user()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /*** prepare storage ***/
        $avatar = $this->getAvatar();

        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => $avatar,
            'remove_avatar' => 0,
        ];

        $this->call('put', 'users/' . $this->user->id, $data, [], ['avatar' => $avatar]);
        $this->assertResponseOk();

        /** @var User $user_fresh */
        $user_fresh = $this->user->fresh();

        $this->assertEquals($this->avatar_name, $user_fresh->avatar);
        $this->assertTrue(Storage::disk('avatar')->exists($this->avatar_name));

        $image = Image::make(Storage::disk('avatar')->get($this->avatar_name));
        $this->assertEquals(128, $image->width());
        $this->assertEquals(128, $image->height());
    }

    /** @test */
    public function update_resize_width_avatar_by_regular_user()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /*** prepare storage ***/
        $avatar = $this->getAvatar(false, 'avatar_width.jpg');

        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => $avatar,
            'remove_avatar' => 0,
        ];

        $this->call('put', 'users/' . $this->user->id, $data, [], ['avatar' => $avatar]);
        $this->assertResponseOk();

        /** @var User $user_fresh */
        $user_fresh = $this->user->fresh();

        $this->assertEquals($this->avatar_name, $user_fresh->avatar);
        $this->assertTrue(Storage::disk('avatar')->exists($this->avatar_name));

        $image = Image::make(Storage::disk('avatar')->get($this->avatar_name));
        $this->assertEquals(200, $image->width());
        $this->assertEquals(50, $image->height());
    }

    /** @test */
    public function update_resize_height_avatar_by_regular_user()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /*** prepare storage ***/
        $avatar = $this->getAvatar(false, 'avatar_height.jpg');

        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => $avatar,
            'remove_avatar' => 0,
        ];

        $this->call('put', 'users/' . $this->user->id, $data, [], ['avatar' => $avatar]);
        $this->assertResponseOk();

        /** @var User $user_fresh */
        $user_fresh = $this->user->fresh();

        $this->assertEquals($this->avatar_name, $user_fresh->avatar);
        $this->assertTrue(Storage::disk('avatar')->exists($this->avatar_name));

        $image = Image::make(Storage::disk('avatar')->get($this->avatar_name));
        $this->assertEquals(50, $image->width());
        $this->assertEquals(200, $image->height());
    }

    /** @test */
    public function update_complete_removal_avatar_by_regular_user()
    {
        $now = Carbon::parse('2017-04-28 12:00:00');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /*** prepare storage ***/
        $this->getAvatar(true);
        $this->user->avatar = $this->avatar_name;
        $this->user->save();

        $this->assertTrue(Storage::disk('avatar')->exists($this->avatar_name));
        $this->assertEquals($this->avatar_name, $this->user->avatar);

        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => '',
            'remove_avatar' => 1,
        ];

        $this->call('put', 'users/' . $this->user->id, $data, [], []);
        $this->assertResponseOk();

        /** @var User $user_fresh */
        $user_fresh = $this->user->fresh();
        $this->assertEquals('', $user_fresh->avatar);
        $this->assertFalse(Storage::disk('avatar')->exists($this->avatar_name));
    }

    /** @test */
    public function update_validation_not_allowed_extension_return_422()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /*** prepare storage ***/
        $avatar = $this->getFile(
            'phpunit_avatar.bmp',
            'image/bmp',
            null,
            'avatar.bmp'
        );

        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => $avatar,
            'remove_avatar' => 0,
        ];

        $this->call('put', 'users/' . $this->user->id, $data, [], ['avatar' => $avatar]);
        $this->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'avatar',
        ]);
    }

    /** @test */
    public function update_validation_too_large_image_return_422()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /*** prepare storage ***/
        $avatar = $this->getFile(
            'phpunit_avatar.jpg',
            'image/jpg',
            null,
            'avatar_1.4mb.jpg'
        );

        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => $avatar,
            'remove_avatar' => 0,
        ];

        $this->call('put', 'users/' . $this->user->id, $data, [], ['avatar' => $avatar]);
        $this->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'avatar',
        ]);
    }

    /** @test */
    public function update_validation_remove_avatar_invalid_checkbox_return_422()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        /*** prepare storage ***/
        $avatar = $this->getFile(
            'phpunit_avatar.jpg',
            'image/jpg',
            null,
            'avatar_1.4mb.jpg'
        );

        $data = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'avatar' => $avatar,
            'remove_avatar' => 2,
        ];

        $this->call('put', 'users/' . $this->user->id, $data, [], ['avatar' => $avatar]);
        $this->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'remove_avatar',
        ]);
    }

    protected function verifyForOwnerOrAdmin($roleType)
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage($roleType, Package::PREMIUM);

        $newUsers = factory(User::class, 7)->create();
        $this->assignUsersToCompany($newUsers, $company, RoleType::DEVELOPER);

        $this->get('/users?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        // get expected users from database
        $users = User::whereIn('id', array_merge([$this->user->id], $newUsers->pluck('id')->all()))
            ->orderBy('id')->get();

        // make sure in response we have all users
        $json = $this->decodeResponseJson();
        $responseUsers = $json['data'];
        $this->assertEquals($users->count(), count($responseUsers));
        $this->assertEquals($this->formatUsers($users), $responseUsers);
    }

    protected function verifyForOrdinaryRole($roleType)
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage($roleType, Package::PREMIUM);

        $otherCompany = factory(Company::class)->create();

        $newUsers = factory(User::class, 9)->create();
        $projectOne = factory(Project::class)->create(['company_id' => $company->id]);
        $projectTwo = factory(Project::class)->create(['company_id' => $otherCompany->id]);
        $projectThree = factory(Project::class)->create(['company_id' => $company->id]);
        $projectFour = factory(Project::class)->create(['company_id' => $company->id]);

        // now we assign current user and other users into different project
        // current user we assign to project 1 and 2
        $projectOne->users()->sync([
            $this->user->id,
            $newUsers[0]->id,
            $newUsers[3]->id,
        ]);
        $projectTwo->users()->sync([
            $this->user->id,
            $newUsers[2]->id,
            $newUsers[4]->id,
        ]);
        $projectThree->users()->sync([
            $newUsers[1]->id,
            $newUsers[5]->id,
            $newUsers[6]->id,
        ]);
        $projectFour->users()->sync([
            $this->user->id,
            $newUsers[7]->id,
            $newUsers[8]->id,
        ]);

        // now we assign users to same company
        $newUsers->each(function ($user) use ($company) {
            $user->companies()
                ->save($company, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        });

        auth()->loginUsingId($this->user->id);

        $this->get('/users?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        // get expected users from database
        $users = User::whereIn('id', [
            $this->user->id,
            $newUsers[0]->id,
            $newUsers[3]->id,
            $newUsers[7]->id,
            $newUsers[8]->id,
        ])->orderBy('id')->get();

        // make sure in response we have all valid users
        $json = $this->decodeResponseJson();
        $responseUsers = $json['data'];
        $this->assertEquals($users->count(), count($responseUsers));
        $this->assertEquals($this->formatUsers($users), $responseUsers);
    }

    /**
     * Generation avatar.
     *
     * @param bool $copy
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile|void
     */
    protected function getAvatar($copy = false, $real_test_file_name = 'avatar.jpg')
    {
        $this->avatar_name = $this->now->timestamp . '_' . $this->user->id . '.jpg';
        $this->assertFalse(Storage::disk('avatar')->exists($this->avatar_name));

        $avatar = $this->getFile(
            'phpunit_avatar.jpg',
            'image/jpeg',
            null,
            $real_test_file_name
        );

        if ($copy) {
            copy(
                storage_path('phpunit_tests/test/phpunit_avatar.jpg'),
                storage_path('user/' . $this->avatar_name)
            );

            return;
        }

        return $avatar;
    }
}
