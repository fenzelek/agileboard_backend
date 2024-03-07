<?php

namespace Tests\Feature\App\Models;

use App\Models\Db\Company;
use App\Models\Db\Package;
use Carbon\Carbon;
use App\Models\Db\User;
use App\Models\Db\Project;
use App\Models\Other\RoleType;
use App\Models\Db\UserAvailability;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Modules\CalendarAvailability\Services\CalendarAvailability;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var CalendarAvailability
     */
    protected $service;

    public function setUp():void
    {
        parent::setUp();
        $this->service = $this->app->make(CalendarAvailability::class);
    }

    public function testFind_verifyNoUsersWhenNoCompanySelected()
    {
        \DB::table('users')->delete();
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        auth()->loginUsingId($this->user->id);

        factory(User::class, 3)->create(['deleted' => 0]);
        factory(User::class, 2)->create(['deleted' => 1]);

        $users = $this->service->find(Carbon::parse('2016-02-10'), Carbon::parse('2016-02-10'));
        // no users - no company selected for user
        $this->assertEquals(0, $users->count());
    }

    public function testFind_verifyUsers_forSuperAdmin()
    {
        \DB::table('users')->delete();
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        $company = factory(Company::class)->create();

        auth()->loginUsingId($this->user->id);
        auth()->user()->setSystemRole();
        auth()->user()->setSelectedCompany($company->id);

        $usersNotDeleted = factory(User::class, 3)->create(['first_name' => 'a', 'last_name' => 'a', 'deleted' => 0]);
        $this->assignUsersToCompany($usersNotDeleted, $company);
        $usersDeleted = factory(User::class, 2)->create(['deleted' => 1]);
        $this->assignUsersToCompany($usersDeleted, $company);

        $users = $this->service->find(Carbon::parse('2016-02-10'), Carbon::parse('2016-02-10'));
        // not deleted users
        $this->assertEquals(3, $users->count());

        // valid users
        $this->assertEquals($usersNotDeleted[0]->id, $users[0]->id);
        $this->assertEquals($usersNotDeleted[1]->id, $users[1]->id);
        $this->assertEquals($usersNotDeleted[2]->id, $users[2]->id);

        // empty availabilities
        $this->assertEquals([], $users[0]->availabilities->toArray());
        $this->assertEquals([], $users[1]->availabilities->toArray());
        $this->assertEquals([], $users[2]->availabilities->toArray());
    }

    public function testFind_verifyUsers_forAdmin()
    {
        \DB::table('users')->delete();
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);

        auth()->loginUsingId($this->user->id);
        auth()->user()->setSystemRole([]);
        auth()->user()->setSelectedCompany($company->id);

        $this->user->update(['first_name' => 'a', 'last_name' => 'a']);
        $usersNotDeleted = factory(User::class, 3)->create(['first_name' => 'a', 'last_name' => 'a', 'deleted' => 0]);
        $this->assignUsersToCompany($usersNotDeleted, $company);
        $usersDeleted = factory(User::class, 2)->create(['deleted' => 1]);
        $this->assignUsersToCompany($usersDeleted, $company);

        $users = $this->service->find(Carbon::parse('2016-02-10'), Carbon::parse('2016-02-10'));
        // myself + not deleted users
        $this->assertEquals(1 + 3, $users->count());

        // valid users
        $this->assertEquals($this->user->id, $users[0]->id);
        $this->assertEquals($usersNotDeleted[0]->id, $users[1]->id);
        $this->assertEquals($usersNotDeleted[1]->id, $users[2]->id);
        $this->assertEquals($usersNotDeleted[2]->id, $users[3]->id);

        // empty availabilities
        $this->assertEquals([], $users[0]->availabilities->toArray());
        $this->assertEquals([], $users[1]->availabilities->toArray());
        $this->assertEquals([], $users[2]->availabilities->toArray());
        $this->assertEquals([], $users[3]->availabilities->toArray());
    }

    public function testFind_verifyUsers_forDeveloper()
    {
        \DB::table('users')->delete();
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        auth()->user()->setSystemRole([]);
        auth()->user()->setSelectedCompany($company->id);

        $this->user->update(['first_name' => 'a', 'last_name' => 'a']);
        $usersNotDeleted = factory(User::class, 3)->create(['first_name' => 'a', 'last_name' => 'a', 'deleted' => 0]);
        $this->assignUsersToCompany($usersNotDeleted, $company);
        $usersDeleted = factory(User::class, 2)->create(['deleted' => 1]);
        $this->assignUsersToCompany($usersDeleted, $company);

        factory(Project::class)->create(['id' => 1, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 5, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 7, 'company_id' => $company->id + 1]);

        \DB::table('project_user')->insert([
            [
                'project_id' => 1,
                'user_id' => $this->user->id,
            ],
            [
                'project_id' => 1,
                'user_id' => $usersNotDeleted[0]->id,
            ],
            [
                'project_id' => 5,
                'user_id' => $this->user->id,
            ],
            [
                'project_id' => 5,
                'user_id' => $usersDeleted[0]->id,
            ],
            [
                'project_id' => 7,
                'user_id' => $this->user->id,
            ],
            [
                'project_id' => 7,
                'user_id' => $usersNotDeleted[2]->id,
            ],
        ]);

        $users = $this->service->find(Carbon::parse('2016-02-10'), Carbon::parse('2016-02-10'));

        //himself + user sharing same project
        $this->assertEquals(1 + 1, $users->count());

        $this->assertEquals($this->user->id, $users[0]->id);
        $this->assertEquals($usersNotDeleted[0]->id, $users[1]->id);

        // empty availabilities
        $this->assertEquals([], $users[0]->availabilities->toArray());
        $this->assertEquals([], $users[1]->availabilities->toArray());
    }

    public function testFind_verifyAvailabilitiesForAdminSingleDay()
    {
        \DB::table('users')->delete();
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        auth()->user()->setSystemRole([]);
        auth()->user()->setSelectedCompany($company->id);

        list($day, $usersNotDeleted, $availabilities) =
            $this->createAvailabilities($company);

        $this->createAvailabilitiesForOtherCompany($usersNotDeleted);

        $users = $this->service->find(Carbon::parse($day), Carbon::parse($day));

        $this->assertEquals(1 + 3, $users->count());

        // $this->user
        $this->assertEquals($this->user->id, $users[0]->id);
        $this->assertEquals(1, $users[0]->availabilities->count());
        $this->assertEquals($availabilities[0]['id'], $users[0]->availabilities[0]->id);

        // $usersNotDeleted[0]
        $this->assertEquals($usersNotDeleted[0]['id'], $users[1]->id);
        $this->assertEquals(0, $users[1]->availabilities->count());

        // $usersNotDeleted[1]
        $this->assertEquals($usersNotDeleted[1]['id'], $users[2]->id);
        $this->assertEquals(2, $users[2]->availabilities->count());
        $this->assertEquals($availabilities[4]['id'], $users[2]->availabilities[0]->id);
        $this->assertEquals($availabilities[3]['id'], $users[2]->availabilities[1]->id);

        // $usersNotDeleted[2]
        $this->assertEquals($usersNotDeleted[2]['id'], $users[3]->id);
        $this->assertEquals(1, $users[3]->availabilities->count());
        $this->assertEquals($availabilities[2]['id'], $users[3]->availabilities[0]->id);
    }

    public function testFind_verifyAvailabilitiesForAdminMultipleDays()
    {
        \DB::table('users')->delete();
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        auth()->user()->setSystemRole([]);
        auth()->user()->setSelectedCompany($company->id);

        list($day, $usersNotDeleted, $availabilities) =
            $this->createAvailabilities($company);

        $this->createAvailabilitiesForOtherCompany($usersNotDeleted);

        $users = $this->service->find(Carbon::parse($day), Carbon::parse($day)->addDay(1));

        $this->assertEquals(1 + 3, $users->count());

        // $this->user
        $this->assertEquals($this->user->id, $users[0]->id);
        $this->assertEquals(2, $users[0]->availabilities->count());
        $this->assertEquals($availabilities[0]['id'], $users[0]->availabilities[0]->id);
        $this->assertEquals($availabilities[5]['id'], $users[0]->availabilities[1]->id);

        // $usersNotDeleted[0]
        $this->assertEquals($usersNotDeleted[0]['id'], $users[1]->id);
        $this->assertEquals(0, $users[1]->availabilities->count());

        // $usersNotDeleted[1]
        $this->assertEquals($usersNotDeleted[1]['id'], $users[2]->id);
        $this->assertEquals(4, $users[2]->availabilities->count());
        $this->assertEquals($availabilities[4]['id'], $users[2]->availabilities[0]->id);
        $this->assertEquals($availabilities[3]['id'], $users[2]->availabilities[1]->id);
        $this->assertEquals($availabilities[9]['id'], $users[2]->availabilities[2]->id);
        $this->assertEquals($availabilities[8]['id'], $users[2]->availabilities[3]->id);

        // $usersNotDeleted[2]
        $this->assertEquals($usersNotDeleted[2]['id'], $users[3]->id);
        $this->assertEquals(2, $users[3]->availabilities->count());
        $this->assertEquals($availabilities[2]['id'], $users[3]->availabilities[0]->id);
        $this->assertEquals($availabilities[7]['id'], $users[3]->availabilities[1]->id);
    }

    public function testFind_verifyAvailabilitiesForDeveloperSingleDay()
    {
        \DB::table('users')->delete();
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        auth()->user()->setSystemRole([]);
        auth()->user()->setSelectedCompany($company->id);

        list($day, $usersNotDeleted, $availabilities, $usersDeleted) =
            $this->createAvailabilities($company);

        factory(Project::class)->create(['id' => 1, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 5, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 7, 'company_id' => $company->id + 1]);

        \DB::table('project_user')->insert([
            [
                'project_id' => 1,
                'user_id' => $this->user->id,
            ],
            [
                'project_id' => 1,
                'user_id' => $usersNotDeleted[1]->id,
            ],
            [
                'project_id' => 5,
                'user_id' => $this->user->id,
            ],
            [
                'project_id' => 5,
                'user_id' => $usersDeleted[0]->id,
            ],
            [
                'project_id' => 7,
                'user_id' => $this->user->id,
            ],
            [
                'project_id' => 7,
                'user_id' => $usersNotDeleted[2]->id,
            ],
        ]);

        $users = $this->service->find(Carbon::parse($day), Carbon::parse($day));

        $this->assertEquals(1 + 1, $users->count());

        // $this->user
        $this->assertEquals($this->user->id, $users[0]->id);
        $this->assertEquals(1, $users[0]->availabilities->count());
        $this->assertEquals($availabilities[0]['id'], $users[0]->availabilities[0]->id);

        // $usersNotDeleted[1]
        $this->assertEquals($usersNotDeleted[1]['id'], $users[1]->id);
        $this->assertEquals(2, $users[1]->availabilities->count());
        $this->assertEquals($availabilities[4]['id'], $users[1]->availabilities[0]->id);
        $this->assertEquals($availabilities[3]['id'], $users[1]->availabilities[1]->id);
    }

    public function testFind_verifyAvailabilitiesForDeveloperMultipleDays()
    {
        \DB::table('users')->delete();
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        auth()->user()->setSystemRole([]);
        auth()->user()->setSelectedCompany($company->id);

        list($day, $usersNotDeleted, $availabilities, $usersDeleted) =
            $this->createAvailabilities($company);

        factory(Project::class)->create(['id' => 1, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 5, 'company_id' => $company->id]);
        factory(Project::class)->create(['id' => 7, 'company_id' => $company->id + 1]);

        \DB::table('project_user')->insert([
            [
                'project_id' => 1,
                'user_id' => $this->user->id,
            ],
            [
                'project_id' => 1,
                'user_id' => $usersNotDeleted[1]->id,
            ],
            [
                'project_id' => 5,
                'user_id' => $this->user->id,
            ],
            [
                'project_id' => 5,
                'user_id' => $usersDeleted[0]->id,
            ],
            [
                'project_id' => 7,
                'user_id' => $this->user->id,
            ],
            [
                'project_id' => 7,
                'user_id' => $usersNotDeleted[2]->id,
            ],
        ]);

        $users = $this->service->find(Carbon::parse($day), Carbon::parse($day)->addDays(1));

        $this->assertEquals(1 + 1, $users->count());

        // $this->user
        $this->assertEquals($this->user->id, $users[0]->id);
        $this->assertEquals(2, $users[0]->availabilities->count());
        $this->assertEquals($availabilities[0]['id'], $users[0]->availabilities[0]->id);
        $this->assertEquals($availabilities[5]['id'], $users[0]->availabilities[1]->id);

        // $usersNotDeleted[1]
        $this->assertEquals($usersNotDeleted[1]['id'], $users[1]->id);
        $this->assertEquals(4, $users[1]->availabilities->count());
        $this->assertEquals($availabilities[4]['id'], $users[1]->availabilities[0]->id);
        $this->assertEquals($availabilities[3]['id'], $users[1]->availabilities[1]->id);
        $this->assertEquals($availabilities[9]['id'], $users[1]->availabilities[2]->id);
        $this->assertEquals($availabilities[8]['id'], $users[1]->availabilities[3]->id);
    }

    protected function createAvailabilities(Company $company)
    {
        $this->user->update(['first_name' => 'a', 'last_name' => 'a']);
        $usersNotDeleted = factory(User::class, 3)->create(['first_name' => 'a', 'last_name' => 'a', 'deleted' => 0]);
        $this->assignUsersToCompany($usersNotDeleted, $company);
        $usersDeleted = factory(User::class, 2)->create(['deleted' => 1]);
        $this->assignUsersToCompany($usersDeleted, $company);

        $day = '2016-02-10';
        $tomorrow = '2016-02-11';

        $availabilities = [
            [
                'time_start' => '12:00:00',
                'time_stop' => '13:00:30',
                'available' => 1,
                'description' => 'Sample description own',
                'user_id' => $this->user->id,
                'day' => $day,
                'company_id' => $company->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $usersDeleted[0]->id,
                'day' => $day,
                'company_id' => $company->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $usersNotDeleted[2]->id,
                'day' => $day,
                'company_id' => $company->id,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '18:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $usersNotDeleted[1]->id,
                'day' => $day,
                'company_id' => $company->id,
            ],
            [
                'time_start' => '14:00:00',
                'time_stop' => '15:00:00',
                'available' => 0,
                'description' => 'Sample description test',
                'user_id' => $usersNotDeleted[1]->id,
                'day' => $day,
                'company_id' => $company->id,
            ],

            [
                'time_start' => '12:00:00',
                'time_stop' => '13:00:30',
                'available' => 1,
                'description' => 'Sample description own',
                'user_id' => $this->user->id,
                'day' => $tomorrow,
                'company_id' => $company->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $usersDeleted[0]->id,
                'day' => $tomorrow,
                'company_id' => $company->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $usersNotDeleted[2]->id,
                'day' => $tomorrow,
                'company_id' => $company->id,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '18:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $usersNotDeleted[1]->id,
                'day' => $tomorrow,
                'company_id' => $company->id,
            ],
            [
                'time_start' => '14:00:00',
                'time_stop' => '15:00:00',
                'available' => 0,
                'description' => 'Sample description test',
                'user_id' => $usersNotDeleted[1]->id,
                'day' => $tomorrow,
                'company_id' => $company->id,
            ],
        ];

        foreach ($availabilities as $key => $av) {
            $avO = UserAvailability::forceCreate($av);
            $availabilities[$key]['id'] = $avO->id;
        }

        return [$day, $usersNotDeleted, $availabilities, $usersDeleted];
    }

    protected function createAvailabilitiesForOtherCompany(Collection $users)
    {
        $newCompany = factory(Company::class)->create();
        $this->assignUsersToCompany($users, $newCompany);

        $day = '2016-02-10';
        $tomorrow = '2016-02-11';

        $availabilities = [
            [
                'time_start' => '12:00:00',
                'time_stop' => '13:00:30',
                'available' => 1,
                'description' => 'Sample description own',
                'user_id' => $this->user->id,
                'day' => $day,
                'company_id' => $newCompany->id,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '18:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $users[1]->id,
                'day' => $day,
                'company_id' => $newCompany->id,
            ],
            [
                'time_start' => '14:00:00',
                'time_stop' => '15:00:00',
                'available' => 0,
                'description' => 'Sample description test',
                'user_id' => $users[1]->id,
                'day' => $day,
                'company_id' => $newCompany->id,
            ],

            [
                'time_start' => '12:00:00',
                'time_stop' => '13:00:30',
                'available' => 1,
                'description' => 'Sample description own',
                'user_id' => $this->user->id,
                'day' => $tomorrow,
                'company_id' => $newCompany->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $users[0]->id,
                'day' => $tomorrow,
                'company_id' => $newCompany->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $users[2]->id,
                'day' => $tomorrow,
                'company_id' => $newCompany->id,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '18:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $users[1]->id,
                'day' => $tomorrow,
                'company_id' => $newCompany->id,
            ],
            [
                'time_start' => '14:00:00',
                'time_stop' => '15:00:00',
                'available' => 0,
                'description' => 'Sample description test',
                'user_id' => $users[1]->id,
                'day' => $tomorrow,
                'company_id' => $newCompany->id,
            ],
        ];

        foreach ($availabilities as $key => $av) {
            UserAvailability::forceCreate($av);
        }
    }
}
