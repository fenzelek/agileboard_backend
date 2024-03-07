<?php

namespace Tests\Feature\App\Modules\CalendarAvailability\Http\Controllers;

use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Models\Db\UserCompany;
use App\Models\Other\ContractType;
use App\Models\Other\DepartmentType;
use App\Models\Other\UserCompanyStatus;
use Carbon\Carbon;
use App\Models\Db\User;
use App\Models\Db\Project;
use App\Models\Other\RoleType;
use App\Helpers\ErrorCode;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CalendarAvailabilityControllerTest extends TestCase
{
    use DatabaseTransactions;
    use CalendarAvailabilityControllerTrait;

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @test
     */
    public function report_pdf_by_month_user_developer()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $otherCompany = factory(Company::class)->create();
        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $this->assignUsersToCompany($newUsers, $company);

        $entry_data = [
            'users_ids' => [
                $this->user->id,
            ],
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        //WHEN
        ob_start();
        $response = $this->json(
            'POST',
            route('availabilities.report.pdf', [
                'date' => Carbon::now()->format('Y-m-d'),
                'in_year' => false,
                'selected_company_id' => $company->id,
            ]),
            $entry_data
        );
        ob_get_clean();

        //THEN
        $response->assertOk();
        $response->assertStatus(200);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @test
     */
    public function report_pdf_by_month_user_admin()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $otherCompany = factory(Company::class)->create();
        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $this->assignUsersToCompany($newUsers, $company);

        $entry_data = [
            'users_ids' => [
                $newUsers->first()->id,
                $this->user->id,
                $newUsers->last()->id,
            ],
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        //WHEN
        ob_start();
        $response = $this->json(
            'POST',
            route('availabilities.report.pdf', [
                'date' => Carbon::now()->format('Y-m-d'),
                'in_year' => false,
                'selected_company_id' => $company->id,
            ]),
            $entry_data
        );
        ob_get_clean();

        //THEN
        $response->assertOk();
        $response->assertStatus(200);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @test
     */
    public function report_pdf_by_year_user_admin()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $this->assignUsersToCompany($newUsers, $company);

        $entry_data = [
            'users_ids' => [
                $newUsers->first()->id,
                $newUsers->last()->id,
                $this->user->id,
            ],
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        //WHEN
        ob_start();
        $response = $this->json(
            'POST',
            route('availabilities.report.pdf', [
                'date' => Carbon::now()->format('Y-m-d'),
                'in_year' => false,
                'selected_company_id' => $company->id,
            ]),
            $entry_data
        );
        ob_get_clean();

        //THEN
        $response->assertOk();
        $response->assertStatus(200);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @test
     */
    public function report_pdf_by_year_user_developer()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $this->assignUsersToCompany($newUsers, $company);

        $entry_data = [
            'users_ids' => [
                $this->user->id,
            ],
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        //WHEN
        ob_start();
        $response = $this->json(
            'POST',
            route('availabilities.report.pdf', [
                'date' => Carbon::now()->format('Y-m-d'),
                'in_year' => false,
                'selected_company_id' => $company->id,
            ]),
            $entry_data
        );
        ob_get_clean();

        //THEN
        $response->assertOk();
        $response->assertStatus(200);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @test
     */
    public function report_pdf_by_year_user_admin_wrong_users_ids()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $wrongUsers = factory(User::class, 2)->create(['deleted' => 0]);

        $this->assignUsersToCompany($newUsers, $company);

        $entry_data = [
            'users_ids' => [
                $wrongUsers->first()->id,
                $wrongUsers->last()->id,
                $newUsers->first()->id,
            ],
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        //WHEN
        $response = $this->json(
            'POST',
            route('availabilities.report.pdf', [
                'date' => Carbon::now()->format('Y-m-d'),
                'in_year' => false,
                'selected_company_id' => $company->id,
            ]),
            $entry_data
        );

        //THEN
        $response->assertStatus(422);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @test
     */
    public function report_pdf_by_year_user_developer_wrong_users_ids()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $wrongUsers = factory(User::class, 2)->create(['deleted' => 0]);

        $this->assignUsersToCompany($newUsers, $company);

        $entry_data = [
            'users_ids' => [
                $wrongUsers->first()->id,
                $wrongUsers->last()->id,
                $newUsers->first()->id,
            ],
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        //WHEN
        $response = $this->json(
            'POST',
            route('availabilities.report.pdf', [
                'date' => Carbon::now()->format('Y-m-d'),
                'in_year' => false,
                'selected_company_id' => $company->id,
            ]),
            $entry_data
        );

        //THEN
        $response->assertStatus(422);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @test
     */
    public function report_pdf_by_year_user_developer_wrong_in_year()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);

        $this->assignUsersToCompany($newUsers, $company);

        $entry_data = [
            'users_ids' => [
                $newUsers->first()->id,
            ],
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        //WHEN
        $response = $this->json(
            'POST',
            route('availabilities.report.pdf', [
                'date' => Carbon::now()->format('Y-m-d'),
                'in_year' => 1234,
                'selected_company_id' => $company->id,
            ]),
            $entry_data
        );

        //THEN
        $response->assertStatus(422);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @test
     */
    public function report_pdf_by_year_user_developer_null_in_year()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);

        $this->assignUsersToCompany($newUsers, $company);

        $entry_data = [
            'users_ids' => [
                $newUsers->first()->id,
            ],
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        //WHEN
        $response = $this->json(
            'POST',
            route('availabilities.report.pdf', [
                'date' => Carbon::now()->format('Y-m-d'),
                'in_year' => null,
                'selected_company_id' => $company->id,
            ]),
            $entry_data
        );

        //THEN
        $response->assertStatus(422);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @test
     */
    public function report_pdf_by_year_user_admin_empty_users_ids()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);

        $this->assignUsersToCompany($newUsers, $company);

        $entry_data = [
            'users_ids' => [],
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        //WHEN
        $response = $this->json(
            'POST',
            route('availabilities.report.pdf', [
                'date' => Carbon::now()->format('Y-m-d'),
                'in_year' => false,
                'selected_company_id' => $company->id,
            ]),
            $entry_data
        );

        //THEN
        $response->assertStatus(422);
    }

    /**
     * @test
     */
    public function testStore_withNoCompanySelected()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        //WHEN
        $response = $this->post('/users/' . $this->user->id . '/availabilities/' .
            Carbon::now()->format('Y-m-d'), [
            'availabilities' => [
                ['time_start' => 'test', 'available' => true],
                ['time_start' => '08:23:23', 'time_stop' => 'test'],
            ],
        ]);

        //THEN
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @test
     */
    public function testStore_withInvalidData()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRole(
            RoleType::ADMIN,
            UserCompanyStatus::REFUSED,
            [],
            Package::PREMIUM
        );
        auth()->loginUsingId($this->user->id);

        $newUser = factory(User::class)->create(['deleted' => 0]);

        //WHEN
        $response = $this->post('/users/' . $newUser->id . '/availabilities/' .
            Carbon::now()->format('Y-m-d') . '?selected_company_id=' . $company->id, [
            'availabilities' => [
                ['time_start' => 'test', 'available' => true],
                ['time_start' => '08:23:23', 'time_stop' => 'test'],
            ],
        ]);
        //THEN
        // permission is based on user company, but it has to be approved, otherwise it means that
        // user has no role assigned for this company so action cannot be performed
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @test
     */
    public function testStore_withValidDataWhenAdmin()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        $otherCompany = factory(Company::class)->create();
        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $this->assignUsersToCompany($newUsers, $company);
        $today = Carbon::now();
        $tomorrow = with(clone $today)->addDay(1);

        $this->createVerifyAdminAvailabilities(
            $newUsers,
            $today,
            $company,
            $otherCompany,
            $tomorrow
        );

        $newAvailabilities = $this->getNewAdminAvailabilities();

        $expectedAvailabilities =
            $this->getExpectedAvailabilities($newAvailabilities, $today);

        //WHEN
        $response = $this->post('/users/' . $newUsers[0]->id . '/availabilities/' .
            $today->format('Y-m-d') . '?selected_company_id=' . $company->id, [
            'availabilities' => $newAvailabilities,
        ]);

        //THEN
        $response->assertStatus(201);
        $response->assertJsonFragment([
            'data' => [
                $expectedAvailabilities[1],
                $expectedAvailabilities[0],
            ],
        ]);

        // make sure the order in response is appropriate
        $json = $response->json()['data'];
        $this->assertEquals($expectedAvailabilities[1], $json[1]);
        $this->assertEquals($expectedAvailabilities[0], $json[0]);
        $this->assertAdminAvailabilitiesDB(
            $newUsers,
            $today,
            $company,
            $otherCompany,
            $tomorrow,
            $expectedAvailabilities
        );
    }

    /**
     * @test
     */
    public function testStore_withValidDataWhenUser()
    {
        Notification::fake();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);

        $admin = factory(User::class, 1)->create(['deleted' => 0]);
        $this->assignUsersToCompany($admin, $company, RoleType::ADMIN);
        $owner = factory(User::class, 1)->create(['deleted' => 0]);
        $this->assignUsersToCompany($owner, $company, RoleType::OWNER);

        $today = Carbon::now();
        $tomorrow = with(clone $today)->addDay(1);

        $this->createSampleAvailabilitiesForUsers($today, $company, $tomorrow);

        $newAvailabilities = $this->getNewAvailabilities();
        $expectedAvailabilities = $this->getExpectedDeveloperAvailabilities($today);

        $response = $this->post('/users/availabilities/own/' .
            $today->format('Y-m-d') . '?selected_company_id=' . $company->id, [
            'availabilities' => $newAvailabilities,
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'data' => [
                $expectedAvailabilities[0],
                $expectedAvailabilities[1],
                $expectedAvailabilities[2],
            ],
        ]);

        // make sure the order in response is appropriate
        $json = $response->json()['data'];
        $this->assertEquals($expectedAvailabilities[0], $json[0]);
        $this->assertEquals($expectedAvailabilities[1], $json[1]);
        $this->assertEquals($expectedAvailabilities[2], $json[2]);
        $this->verifyAvailabilitiesInDatabase($today, $company, $tomorrow);
    }

    public function testStore_itGetsErrorWhenAdminTriesToCreateForRefusedUser()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $this->assignUsersToCompany(
            $newUsers,
            $company,
            RoleType::DEVELOPER,
            UserCompanyStatus::REFUSED
        );
        $today = Carbon::now();
        $tomorrow = with(clone $today)->addDay(1);

        $response = $this->post('/users/' . $newUsers[0]->id . '/availabilities/' .
            $today->format('Y-m-d') . '?selected_company_id=' . $company->id, [
            'availabilities' => [],
        ]);

        $this->verifyResponseValidation($response, ['user']);
    }

    /**
     * @test
     *
     * @dataProvider individualError
     */
    public function testStore_withInvalidDataWhenUser($entry_data)
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);

        $today = Carbon::now();
        $tomorrow = with(clone $today)->addDay(1);

        $this->createSampleAvailabilitiesForUsers($today, $company, $tomorrow);

        $response = $this->post('/users/availabilities/own/' .
            $today->format('Y-m-d') . '?selected_company_id=' . $company->id, [
            'availabilities' => $entry_data,
        ]);

        $response->assertStatus(422);
    }

    public function testStore_withValidDataWhenNotAdminForOtherUser()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $today = Carbon::now();

        $newAvailabilities = [];

        $response = $this->post('/users/' . $newUsers[0]->id . '/availabilities/' .
            $today->format('Y-m-d') . '?selected_company_id=' . $company->id, [
            'availabilities' => $newAvailabilities,
        ]);

        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    public function testStore_withValidDataWhenNotForHimself()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $today = Carbon::now();
        $tomorrow = with(clone $today)->addDay(1);

        // create sample availabilities for users
        \DB::table('user_availability')->insert([
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $this->user->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
        ]);

        // verify number of results in database
        $this->assertEquals(1, \DB::table('user_availability')
            ->where('user_id', $this->user->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());

        $newAvailabilities = [
            [
                'time_start' => '12:00:00',
                'time_stop' => '15:00:00',
                'available' => true,
                'description' => 'Sample description',
            ],
        ];

        $expectedAvailabilities =
            $this->getExpectedAvailabilities($newAvailabilities, $today);

        $response = $this->post('/users/' . $this->user->id . '/availabilities/' .
            $today->format('Y-m-d') . '?selected_company_id=' . $company->id, [
            'availabilities' => $newAvailabilities,
        ]);

        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);

        // verify number of results in database
        $this->assertEquals(1, \DB::table('user_availability')
            ->where('user_id', $this->user->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());
    }

    /** @test */
    public function testShow_whenUserDoesNotExists()
    {
        $response = $this->get('/users/' . 99999999 . '/availabilities/' .
            Carbon::now()->format('Y-m-d'));

        $response->assertStatus(404);
    }

    /** @test */
    public function show_admin_has_displayed_valid_availabilities_for_today()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company);

        $response = $this->get('/users/' . $newUsers[0]->id . '/availabilities/' .
            $today->format('Y-m-d') . '?selected_company_id=' . $company->id);

        $response->assertStatus(200)->assertJsonFragment([
            'data' => [
                $this->formatAvailability($availabilities[4]),
                $this->formatAvailability($availabilities[3]),
            ],
        ]);

        // make sure the order in response is appropriate
        $json = $response->json()['data'];
        $this->assertEquals(
            $this->formatAvailability($availabilities[4]),
            $json[0]
        );
        $this->assertEquals(
            $this->formatAvailability($availabilities[3]),
            $json[1]
        );
    }

    /** @test */
    public function show_admin_has_displayed_valid_availabilities_for_tomorrow()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company);

        $response = $this->get('/users/' . $newUsers[0]->id . '/availabilities/' .
            $tomorrow->format('Y-m-d') . '?selected_company_id=' . $company->id);

        $response->assertStatus(200)->assertJsonFragment([
            'data' => [
                $this->formatAvailability($availabilities[5]),
            ],
        ]);
    }

    public function testShow_whenAdminAndUserAssignedToAnotherCompany()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $otherCompany = factory(Company::class)->create();

        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($otherCompany);

        $response = $this->get('/users/' . $newUsers[0]->id . '/availabilities/' .
            $tomorrow->format('Y-m-d') . '?selected_company_id=' . $company->id);

        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    public function testShow_whenAdmin()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company);

        $response = $this->get('/users/' . $newUsers[0]->id . '/availabilities/' .
            $tomorrow->format('Y-m-d') . '?selected_company_id=' . $company->id);

        $response->assertStatus(200);
    }

    public function testShow_whenDeveloper_forHimself()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company);

        $response = $this->get('/users/' . $this->user->id . '/availabilities/' .
            $tomorrow->format('Y-m-d') . '?selected_company_id=' . $company->id);

        $response->assertStatus(200);
    }

    public function testShow_whenDeveloper_forOtherUser_whenNotInSameProject()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company);

        $response = $this->get('/users/' . $newUsers[0]->id . '/availabilities/' .
            $tomorrow->format('Y-m-d') . '?selected_company_id=' . $company->id);

        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    public function testShow_whenDeveloper_forOtherUserInSameProject()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company);

        factory(Project::class)->create(['id' => 1, 'company_id' => $company->id]);

        \DB::table('project_user')->insert([
            [
                'project_id' => 1,
                'user_id' => $this->user->id,
            ],
            [
                'project_id' => 1,
                'user_id' => $newUsers[0]->id,
            ],
        ]);

        $response = $this->get('/users/' . $newUsers[0]->id . '/availabilities/' .
            $tomorrow->format('Y-m-d') . '?selected_company_id=' . $company->id);

        $response->assertStatus(200);
    }

    public function testIndex_withoutParameters()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company);

        $response = $this->get('/users/availabilities?selected_company_id=' . $company->id);

        $this->verifyResponseValidation($response, ['from'], ['limit']);
    }

    /** @test */
    public function index_see_all_when_admin()
    {
        \DB::table('users')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        [$newUsers, $today, $tomorrow, $availabilities, $startOfWeek] =
            $this->prepareGetData($company);

        $response = $this->get('/users/availabilities?from=' . $today->format('Y-m-d') .
            '&limit=4&selected_company_id=' . $company->id);

        $response->assertStatus(200);

        $json = $response->json();

        $data = $json['data'];

        $this->assertEquals($startOfWeek->format('Y-m-d'), $json['date_start']);
        $this->assertEquals(
            $startOfWeek->addDays(3)->format('Y-m-d'),
            $json['date_end']
        );

        $this->assertEquals(1 + $newUsers->count(), count($data));

        $this->assertEquals(array_merge($this->formatUser($this->user), [
            'availabilities' => [
                'data' => [
                    $this->formatAvailability($availabilities[1]),
                    $this->formatAvailability($availabilities[2]),
                ],
            ],
        ]), $data[0]);

        $this->assertEquals(array_merge($this->formatUser($newUsers[0]), [
            'availabilities' => [
                'data' => [
                    $this->formatAvailability($availabilities[4]),
                    $this->formatAvailability($availabilities[3]),
                    $this->formatAvailability($availabilities[5]),
                ],
            ],
        ]), $data[1]);

        $this->assertEquals(array_merge($this->formatUser($newUsers[1]), [
            'availabilities' => [
                'data' => [
                    $this->formatAvailability($availabilities[7]),
                ],
            ],
        ]), $data[2]);

        $this->assertEquals(array_merge($this->formatUser($newUsers[2]), [
            'availabilities' => [
                'data' => [

                ],
            ],
        ]), $data[3]);

        $this->assertEquals(array_merge($this->formatUser($newUsers[3]), [
            'availabilities' => [
                'data' => [],
            ],
        ]), $data[4]);
    }

    /** @test */
    public function index_admin_wont_see_clients()
    {
        \DB::table('users')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        auth()->loginUsingId($this->user->id);
        [$newUsers, $today, $tomorrow, $availabilities, $startOfWeek] =
            $this->prepareGetData($company, RoleType::CLIENT);

        $response = $this->get('/users/availabilities?from=' . $today->format('Y-m-d') .
            '&limit=4&selected_company_id=' . $company->id);

        $response->assertStatus(200);

        $json = $response->json();

        $data = $json['data'];

        $this->assertEquals($startOfWeek->format('Y-m-d'), $json['date_start']);
        $this->assertEquals(
            $startOfWeek->addDays(3)->format('Y-m-d'),
            $json['date_end']
        );

        $this->assertEquals(1, count($data));

        $this->assertEquals(array_merge($this->formatUser($this->user), [
            'availabilities' => [
                'data' => [
                    $this->formatAvailability($availabilities[1]),
                    $this->formatAvailability($availabilities[2]),
                ],
            ],
        ]), $data[0]);
    }

    /** @test */
    public function index_see_only_own_when_developer_without_projects()
    {
        \DB::table('users')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company);

        $query = [
            'from' => $today->format('Y-m-d'),
            'limit' => 4,
            'selected_company_id' => $company->id,
        ];

        $response = $this->get('/users/availabilities?' . Arr::query($query));

        $response->assertStatus(200);

        $data = $response->json()['data'];

        $this->assertEquals(1, count($data));

        $this->assertEquals(array_merge($this->formatUser($this->user), [
            'availabilities' => [
                'data' => [
                    $this->formatAvailability($availabilities[1]),
                    $this->formatAvailability($availabilities[2]),
                ],
            ],
        ]), $data[0]);
    }

    /** @test */
    public function index_sorts_working()
    {
        \DB::table('users')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company);

        $this->setUserCompanyDepartment($newUsers[1], $company, DepartmentType::DEVELOPERS);
        $this->setUserCompanyDepartment($newUsers[3], $company, DepartmentType::TELECOMMUNICATION);
        $this->setUserCompanyContractType($newUsers[0], $company, ContractType::EMPLOYMENT_CONTRACT);

        $query = [
            'from' => $today->format('Y-m-d'),
            'limit' => 4,
            'selected_company_id' => $company->id,
            'sorts' => [
                ['field' => 'last_name', 'direction' => 'asc'],
                ['field' => 'department', 'direction' => 'asc'],
                ['field' => 'contract_type', 'direction' => 'asc'],
            ],
        ];

        $response = $this->get('/users/availabilities?' . Arr::query($query));

        $response->assertStatus(200);
    }

    /** @test */
    public function index_DepartmentFilterApplied_OnlyUsersFromDepartment()
    {
        \DB::table('users')->delete();

        $department = DepartmentType::DEVELOPERS;
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        [$newUsers, $today, $tomorrow, $availabilities] = $this->prepareGetData($company);
        UserCompany::query()
            ->where('user_id', $this->user->id)
            ->where('company_id', $company->id)
            ->update(['department' => $department]);

        $response = $this->get('/users/availabilities?' . Arr::query([
                'from' => $today->format('Y-m-d'),
                'limit' => 4,
                'selected_company_id' => $company->id,
                'department' => $department,
            ]));
        $data = $response->json()['data'];

        $this->assertSame(1, count($data));
        $this->assertEquals(array_merge($this->formatUser($this->user), [
            'availabilities' => [
                'data' => [
                    $this->formatAvailability($availabilities[1]),
                    $this->formatAvailability($availabilities[2]),
                ],
            ],
        ]), $data[0]);
    }

    /** @test */
    public function index_DepartmentFilterApplied_UsersFromOtherDepartmentNotPresent()
    {
        \DB::table('users')->delete();

        $department = DepartmentType::DEVELOPERS;
        $other_department = DepartmentType::TELECOMMUNICATION;
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        [$newUsers, $today, $tomorrow, $availabilities] = $this->prepareGetData($company);
        UserCompany::query()
            ->where('user_id', $this->user->id)
            ->where('company_id', $company->id)
            ->update(['department' => $department]);

        $response = $this->get('/users/availabilities?' . Arr::query([
                'from' => $today->format('Y-m-d'),
                'limit' => 4,
                'selected_company_id' => $company->id,
                'department' => $other_department,
            ]));
        $data = $response->json()['data'];

        $this->assertSame(0, count($data));
    }

    /** @test */
    public function index_see_others_when_developer_and_assigned_to_same_project()
    {
        \DB::table('users')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company);

        factory(Project::class)->create(['id' => 1, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 2, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 3, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 8, 'company_id' => $company->id]);
        // below project we create for different company to make sure it won't be displayed
        // also in case both users are in same project (but it's other company project)
        factory(Project::class)->create(['id' => 9, 'company_id' => $company->id + 1]);

        \DB::table('project_user')->insert(
            [
                [
                    'project_id' => 1,
                    'user_id' => $this->user->id,
                ],
                [
                    'project_id' => 1,
                    'user_id' => $newUsers[0]->id,
                ],
                [
                    'project_id' => 3,
                    'user_id' => $this->user->id,
                ],
                [
                    'project_id' => 3,
                    'user_id' => $newUsers[2]->id,
                ],
                [
                    'project_id' => 2,
                    'user_id' => $newUsers[1]->id,
                ],
                [
                    'project_id' => 8,
                    'user_id' => $this->user->id,
                ],
                [
                    'project_id' => 8,
                    'user_id' => $newUsers[3]->id,
                ],
                [
                    'project_id' => 9,
                    'user_id' => $this->user->id,
                ],
                [
                    'project_id' => 9,
                    'user_id' => $newUsers[1]->id,
                ],

            ]
        );

        // now we create user with deleted status in company to make sure it won't be shown
        $other_user = factory(User::class)->create(['deleted' => 0]);
        $this->assignUsersToCompany(
            collect([$other_user]),
            $company,
            RoleType::DEVELOPER,
            UserCompanyStatus::DELETED
        );
        \DB::table('project_user')->insert(
            [
                'project_id' => 1,
                'user_id' => $other_user->id,
            ]
        );

        $response = $this->get('/users/availabilities?from=' . $today->format('Y-m-d') .
            '&limit=4&selected_company_id=' . $company->id);

        $response->assertStatus(200);

        $data = $response->json()['data'];

        $this->assertEquals(1 + 3, count($data));

        $this->assertEquals(array_merge($this->formatUser($this->user), [
            'availabilities' => [
                'data' => [
                    $this->formatAvailability($availabilities[1]),
                    $this->formatAvailability($availabilities[2]),
                ],
            ],
        ]), $data[0]);

        $this->assertEquals(array_merge($this->formatUser($newUsers[0]), [
            'availabilities' => [
                'data' => [
                    $this->formatAvailability($availabilities[4]),
                    $this->formatAvailability($availabilities[3]),
                    $this->formatAvailability($availabilities[5]),
                ],
            ],
        ]), $data[1]);

        $this->assertEquals(array_merge($this->formatUser($newUsers[2]), [
            'availabilities' => [
                'data' => [

                ],
            ],
        ]), $data[2]);

        $this->assertEquals(array_merge($this->formatUser($newUsers[3]), [
            'availabilities' => [
                'data' => [],
            ],
        ]), $data[3]);
    }

    /** @test */
    public function index_wont_see_clients_when_developer()
    {
        \DB::table('users')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);
        auth()->loginUsingId($this->user->id);
        [$newUsers, $today, $tomorrow, $availabilities] =
            $this->prepareGetData($company, RoleType::CLIENT);

        factory(Project::class)->create(['id' => 1, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 2, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 3, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 8, 'company_id' => $company->id]);
        // below project we create for different company to make sure it won't be displayed
        // also in case both users are in same project (but it's other company project)
        factory(Project::class)->create(['id' => 9, 'company_id' => $company->id + 1]);

        \DB::table('project_user')->insert(
            [
                [
                    'project_id' => 1,
                    'user_id' => $this->user->id,
                ],
                [
                    'project_id' => 1,
                    'user_id' => $newUsers[0]->id,
                ],
                [
                    'project_id' => 3,
                    'user_id' => $this->user->id,
                ],
                [
                    'project_id' => 3,
                    'user_id' => $newUsers[2]->id,
                ],
                [
                    'project_id' => 2,
                    'user_id' => $newUsers[1]->id,
                ],
                [
                    'project_id' => 8,
                    'user_id' => $this->user->id,
                ],
                [
                    'project_id' => 8,
                    'user_id' => $newUsers[3]->id,
                ],
                [
                    'project_id' => 9,
                    'user_id' => $this->user->id,
                ],
                [
                    'project_id' => 9,
                    'user_id' => $newUsers[1]->id,
                ],

            ]
        );

        // now we create user with deleted status in company to make sure it won't be shown
        $other_user = factory(User::class)->create(['deleted' => 0]);
        $this->assignUsersToCompany(
            collect([$other_user]),
            $company,
            RoleType::DEVELOPER,
            UserCompanyStatus::DELETED
        );
        \DB::table('project_user')->insert(
            [
                'project_id' => 1,
                'user_id' => $other_user->id,
            ]
        );

        $response = $this->get('/users/availabilities?from=' . $today->format('Y-m-d') .
            '&limit=4&selected_company_id=' . $company->id);

        $response->assertStatus(200);

        $data = $response->json()['data'];

        $this->assertEquals(1, count($data));

        $this->assertEquals(array_merge($this->formatUser($this->user), [
            'availabilities' => [
                'data' => [
                    $this->formatAvailability($availabilities[1]),
                    $this->formatAvailability($availabilities[2]),
                ],
            ],
        ]), $data[0]);
    }

    /** @test */
    public function store_withValidDataWithDescriptionWhenOwner()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $today = Carbon::now();
        $tomorrow = with(clone $today)->addDay(1);

        // verify there is no data with this user id, day and company id
        $this->assertEquals(0, \DB::table('user_availability')
            ->where('user_id', $this->user->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());

        $availabilities = [
            [
                'time_start' => '12:00:00',
                'time_stop' => '15:00:00',
                'available' => true,
                'description' => 'Sample description',
            ],
        ];

        $response = $this->post('/users/' . $this->user->id . '/availabilities/' .
            $today->format('Y-m-d') . '?selected_company_id=' . $company->id, [
            'availabilities' => $availabilities,
        ]);

        $response->assertStatus(201)->assertJsonFragment([
            'description' => 'Sample description',
        ]);

        // verify number of results in database
        $this->assertEquals(1, \DB::table('user_availability')
            ->where('user_id', $this->user->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());

        // verify if new records are in database
        $this->assertDatabaseHas(
            'user_availability',
            array_merge($availabilities[0], [
                'day' => $today->format('Y-m-d'),
                'user_id' => $this->user->id,
                'company_id' => $company->id,
            ])
        );
    }
}
