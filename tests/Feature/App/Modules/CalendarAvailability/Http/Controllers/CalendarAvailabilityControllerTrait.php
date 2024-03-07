<?php

namespace Tests\Feature\App\Modules\CalendarAvailability\Http\Controllers;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Models\Db\UserCompany;
use App\Models\Other\RoleType;
use App\Modules\CalendarAvailability\Models\UserAvailabilitySourceType;
use Carbon\Carbon;

trait CalendarAvailabilityControllerTrait
{
    /**
     * @param $newUsers
     * @param $company
     * @param $otherCompany
     */
    private function setDataDB($newUsers, $company, $otherCompany): void
    {
        $today = Carbon::now();
        $tomorrow = with(clone $today)->addDay(1);

        \DB::table('user_availability')->insert([
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[0]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '17:00:00',
                'available' => 1,
                'description' => 'overtime',
                'user_id' => $newUsers[0]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '17:00:00',
                'available' => 1,
                'description' => 'overtime',
                'user_id' => $newUsers[0]->id,
                'day' => Carbon::now()->subDays(1)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            // not current data different company
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Different company',
                'user_id' => $newUsers[0]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $otherCompany->id,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '17:00:00',
                'available' => 1,
                'description' => 'overtime',
                'user_id' => $newUsers[0]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $otherCompany->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[0]->id,
                'day' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'company_id' => $otherCompany->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[0]->id,
                'day' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '10:00:00',
                'time_stop' => '13:00:00',
                'available' => 1,
                'description' => 'overtime',
                'user_id' => $newUsers[1]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '03:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '15:30:00',
                'available' => 1,
                'description' => 'overtime',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[1]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[0]->id,
                'day' => Carbon::now()->subDays(4)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[0]->id,
                'day' => Carbon::now()->subDays(5)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[0]->id,
                'day' => Carbon::now()->subMonth()->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '08:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Working',
                'user_id' => $newUsers[0]->id,
                'day' => Carbon::now()->subYear()->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subMonth()->subDays(4)->format('Y-m-d'),
                'company_id' => $company->id,
            ],

            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subMonth()->subDays(3)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subMonth()->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subMonths(5)->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'hollyday',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subMonths(5)->subDays(3)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subMonths(5)->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subMonths(7)->subDays(10)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subYear()->subDays(10)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'hollyday',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subYear()->subDays(3)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '08:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Working',
                'user_id' => $newUsers[1]->id,
                'day' => Carbon::now()->subYear()->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '08:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Working',
                'user_id' => $this->user->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
        ]);
    }

    /**
     * @param $newUsers
     * @param Carbon $today
     * @param $company
     * @param $otherCompany
     * @param $tomorrow
     *
     * @return void
     */
    protected function createVerifyAdminAvailabilities($newUsers, Carbon $today, $company, $otherCompany, $tomorrow): void
    {
        \DB::table('user_availability')->insert([
            [
                'time_start' => '02:00:00',
                'time_stop' => '03:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'status' => 'CONFIRMED',
                'user_id' => $newUsers[0]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
        ]);
        \DB::table('user_availability')->insert([
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[0]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            //other company
            [
                'time_start' => '01:00:00',
                'time_stop' => '02:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[0]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $otherCompany->id,
            ],
            //other day
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[0]->id,
                'day' => $tomorrow->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            //other user
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[1]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
        ]);

        // verify number of results in database
        $this->assertEquals(2, \DB::table('user_availability')
            ->where('user_id', $newUsers[0]->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());
        $this->assertEquals(1, \DB::table('user_availability')
            ->where('user_id', $newUsers[0]->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $otherCompany->id)
            ->count());
        $this->assertEquals(1, \DB::table('user_availability')
            ->where('user_id', $newUsers[0]->id)
            ->where('day', $tomorrow->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());
        $this->assertEquals(1, \DB::table('user_availability')
            ->where('user_id', $newUsers[1]->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());
    }

    /**
     * @return array[]
     */
    protected function getNewAdminAvailabilities(): array
    {
        $newAvailabilities = [
            [
                'time_start' => '01:00:00',
                'time_stop' => '02:00:00',
                'available' => true,
                'description' => 'Sample description',
                'overtime' => true,
                'status' => 'CONFIRMED',
                'source' => UserAvailabilitySourceType::INTERNAL,
            ],
            [
                'time_start' => '02:00:00',
                'time_stop' => '04:00:00',
                'available' => false,
                'description' => "Sorry I'm out ",
                'overtime' => false,
                'status' => 'CONFIRMED',
                'source' => UserAvailabilitySourceType::INTERNAL,
            ],
        ];

        return $newAvailabilities;
    }

    /**
     * @param $newUsers
     * @param Carbon $today
     * @param $company
     * @param $otherCompany
     * @param $tomorrow
     * @param array $expectedAvailabilities
     *
     * @return void
     */
    protected function assertAdminAvailabilitiesDB($newUsers, Carbon $today, $company, $otherCompany, $tomorrow, array $expectedAvailabilities): void
    {
        $this->assertEquals(2, \DB::table('user_availability')
            ->where('user_id', $newUsers[0]->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());
        $this->assertEquals(1, \DB::table('user_availability')
            ->where('user_id', $newUsers[0]->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $otherCompany->id)
            ->count());
        $this->assertEquals(1, \DB::table('user_availability')
            ->where('user_id', $newUsers[0]->id)
            ->where('day', $tomorrow->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());
        $this->assertEquals(1, \DB::table('user_availability')
            ->where('user_id', $newUsers[1]->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());

        // verify if new records are in database
        $this->assertDatabaseHas(
            'user_availability',
            array_merge($expectedAvailabilities[0], [
                'user_id' => $newUsers[0]->id,
                'company_id' => $company->id,
            ])
        );
        $this->assertDatabaseHas(
            'user_availability',
            array_merge($expectedAvailabilities[1], [
                'user_id' => $newUsers[0]->id,
                'company_id' => $company->id,
            ])
        );
    }

    protected function prepareGetData(Company $company, $role_slug = RoleType::DEVELOPER)
    {
        $this->user->update(['first_name' => 'a', 'last_name' => 'a']);
        $newUsers = collect([
            factory(User::class)->create(['first_name' => 'b', 'last_name' => 'b']),
            factory(User::class)->create(['first_name' => 'c', 'last_name' => 'c']),
            factory(User::class)->create(['first_name' => 'd', 'last_name' => 'd']),
            factory(User::class)->create(['first_name' => 'e', 'last_name' => 'e']),
        ]);
        $this->assignUsersToCompany($newUsers, $company, $role_slug);
        $today = Carbon::parse('2016-03-08');
        $tomorrow = with(clone $today)->addDay(1);
        $yesterday = with(clone $today)->subDay(1);
        $startOfWeek = clone($yesterday);
        $inPreviousWeek = with(clone $today)->subDays(2);

        $availabilities = [
            [
                'time_start' => '12:00:00',
                'time_stop' => '13:00:30',
                'available' => 1,
                'description' => 'Sample description in previous week',
                'user_id' => $this->user->id,
                'day' => $inPreviousWeek->format('Y-m-d'),
                'company_id' => $company->id,
                'status' => 'ADDED',
                'overtime' => false,
            ],

            [
                'time_start' => '12:00:00',
                'time_stop' => '13:00:30',
                'available' => 1,
                'description' => 'Sample description own yesterday',
                'user_id' => $this->user->id,
                'day' => $yesterday->format('Y-m-d'),
                'company_id' => $company->id,
                'status' => 'ADDED',
                'overtime' => false,
            ],
            [
                'time_start' => '12:00:00',
                'time_stop' => '13:00:30',
                'available' => 1,
                'description' => 'Sample description own',
                'user_id' => $this->user->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
                'status' => 'ADDED',
                'overtime' => false,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[0]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
                'status' => 'ADDED',
                'overtime' => false,
            ],
            [
                'time_start' => '13:00:00',
                'time_stop' => '14:00:00',
                'available' => 0,
                'description' => 'Sample description test 2',
                'user_id' => $newUsers[0]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
                'status' => 'ADDED',
                'overtime' => false,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[0]->id,
                'day' => $tomorrow->format('Y-m-d'),
                'company_id' => $company->id,
                'status' => 'ADDED',
                'overtime' => false,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[2]->id,
                'day' => with(clone $tomorrow)->addDay(2)->format('Y-m-d'),
                'company_id' => $company->id,
                'status' => 'ADDED',
                'overtime' => false,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[1]->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
                'status' => 'ADDED',
                'overtime' => false,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $today)->addDays(20)->format('Y-m-d'),
                'company_id' => $company->id,
                'status' => 'ADDED',
                'overtime' => false,
            ],
        ];

        // create sample availabilities for users
        \DB::table('user_availability')->insert($availabilities);

        return [$newUsers, $today, $tomorrow, $availabilities, $startOfWeek];
    }

    protected function formatUserCompany(User $user, Company $company): array
    {
        /** @var UserCompany $user_company */
        $user_company = UserCompany::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        return [
            'data' => [
                'user_id' => $user_company->user_id,
                'company_id' => $user_company->company_id,
                'role_id' => $user_company->role_id,
                'status' => $user_company->status,
                'title' => $user_company->title,
                'skills' => $user_company->skills,
                'description' => $user_company->description,
                'department' => $user_company->department,
                'contract_type' => $user_company->contract_type,
            ],
        ];
    }

    protected function formatAvailability(array $av)
    {
        unset($av['user_id'], $av['company_id']);

        $av['available'] = (bool) $av['available'];
        $av['source'] = UserAvailabilitySourceType::INTERNAL;

        return $av;
    }

    protected function getExpectedAvailabilities(
        array $availabilities,
        Carbon $date
    ) {
        $expectedAvailabilities = [];

        foreach ($availabilities as $av) {
            $expectedAvailabilities[] =
                array_merge($av, ['day' => $date->format('Y-m-d')]);
        }

        return $expectedAvailabilities;
    }

    /**
     * @param Carbon $today
     * @param $company
     * @param $tomorrow
     *
     * @return void
     */
    protected function createSampleAvailabilitiesForUsers(Carbon $today, $company, $tomorrow): void
    {
        // create sample availabilities for users
        \DB::table('user_availability')->insert([
            [
                'time_start' => '11:00:00',
                'time_stop' => '12:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $this->user->id,
                'status' => 'CONFIRMED',
                'overtime' => true,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '13:00:00',
                'time_stop' => '14:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $this->user->id,
                'status' => 'ADDED',
                'overtime' => true,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],

        ]);
        \DB::table('user_availability')->insert([
            [
                'time_start' => '10:00:00',
                'time_stop' => '11:30:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $this->user->id,
                'day' => $today->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '02:00:00',
                'time_stop' => '03:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'user_id' => $this->user->id,
                'day' => $tomorrow->format('Y-m-d'),
                'company_id' => $company->id,
            ],
        ]);
    }

    /**
     * @return array[]
     */
    protected function getNewAvailabilities(): array
    {
        $newAvailabilities = [
            [
                'time_start' => '10:00:00',
                'time_stop' => '10:30:00',
                'available' => true,
                'description' => 'Sample description',
                'status' => 'ADDED',
                'overtime' => false,
                'source' => UserAvailabilitySourceType::INTERNAL,
            ],
            [
                'time_start' => '13:00:00',
                'time_stop' => '14:00:00',
                'available' => false,
                'description' => "Sorry I'm out ",
                'status' => 'ADDED',
                'source' => UserAvailabilitySourceType::INTERNAL,
            ],
        ];

        return $newAvailabilities;
    }

    /**
     * @param Carbon $today
     *
     * @return array[]
     */
    protected function getExpectedDeveloperAvailabilities(Carbon $today): array
    {
        $responseAvailabilities = [
            [
                'day' => $today->format('Y-m-d'),
                'time_start' => '10:00:00',
                'time_stop' => '10:30:00',
                'available' => true,
                'description' => 'Sample description',
                'overtime' => false,
                'status' => 'ADDED',
                'source' => UserAvailabilitySourceType::INTERNAL,
            ],
            [
                'day' => $today->format('Y-m-d'),
                'time_start' => '11:00:00',
                'time_stop' => '12:00:00',
                'available' => true,
                'description' => 'Sample description test',
                'overtime' => true,
                'status' => 'CONFIRMED',
                'source' => UserAvailabilitySourceType::INTERNAL,
            ],
            [
                'day' => $today->format('Y-m-d'),
                'time_start' => '13:00:00',
                'time_stop' => '14:00:00',
                'available' => false,
                'description' => "Sorry I'm out ",
                'overtime' => false,
                'status' => 'ADDED',
                'source' => UserAvailabilitySourceType::INTERNAL,
            ],
        ];

        return $responseAvailabilities;
    }

    /**
     * @param Carbon $today
     * @param $company
     * @param $tomorrow
     *
     * @return void
     */
    protected function verifyAvailabilitiesInDatabase(Carbon $today, $company, $tomorrow): void
    {
        // verify number of results in database
        $this->assertEquals(3, \DB::table('user_availability')
            ->where('user_id', $this->user->id)
            ->where('day', $today->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());
        $this->assertEquals(1, \DB::table('user_availability')
            ->where('user_id', $this->user->id)
            ->where('day', $tomorrow->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->count());
    }

    public function individualError()
    {
        return [
            'time next day' => [
                [
                    [
                        'time_start' => '23:00:00',
                        'time_stop' => '01:30:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'time overlap each other' => [
                [
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '12:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '11:00:00',
                        'time_stop' => '15:30:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'time overlap each other different sequence' => [
                [
                    [
                        'time_start' => '11:00:00',
                        'time_stop' => '15:30:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '12:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'time cover each other' => [
                [
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '10:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '07:00:00',
                        'time_stop' => '15:30:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'time inside each other' => [
                [
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '16:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '09:00:00',
                        'time_stop' => '13:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'time same' => [
                [
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '16:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '16:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'time same start' => [
                [
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '16:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '19:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'time same stop' => [
                [
                    [
                        'time_start' => '07:00:00',
                        'time_stop' => '16:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '16:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'first new time cover all time' => [
                [
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '16:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '12:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '12:00:00',
                        'time_stop' => '16:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'new time in two periods time' => [
                [
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '12:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '12:00:00',
                        'time_stop' => '16:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                    [
                        'time_start' => '09:00:00',
                        'time_stop' => '13:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'time_start not time' => [
                [
                    [
                        'time_start' => 'time',
                        'time_stop' => '16:00:00',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'time_stop not time' => [
                [
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '2$',
                        'available' => true,
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'available not bool' => [
                [
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '16:00:00',
                        'available' => 'Hello',
                        'description' => 'Sample description',
                        'overtime' => false,
                    ],
                ],
            ],

            'overtime not bool' => [
                [
                    [
                        'time_start' => '08:00:00',
                        'time_stop' => '16:00:00',
                        'available' => false,
                        'description' => 'Sample description',
                        'overtime' => 'My_time',
                    ],
                ],
            ],
        ];
    }

    private function setUserCompanyDepartment(User $user, Company $company, string $department)
    {
        UserCompany::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'department' => $department,
        ]);
    }

    private function setUserCompanyContractType(User $user, Company $company, string $contract_type)
    {
        UserCompany::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'contract_type' => $contract_type,
        ]);
    }
}
