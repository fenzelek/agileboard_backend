<?php

namespace Tests\Feature\App\Modules\CalendarAvailability\Service\CalendarAvailability;

use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait CalendarAvailabilityTrait
{
    /**
     * @param $company
     *
     * @return Collection
     */
    protected function CreateAndAssignTwoUsers($company): Collection
    {
        $newUserOne = factory(User::class)->create([
            'first_name' => 'Alen',
            'last_name' => 'Alenowski',
            'deleted' => 0,
        ]);
        $newUserTwo = factory(User::class)->create([
            'first_name' => 'Bazel',
            'last_name' => 'Bazelowski',
            'deleted' => 0,
        ]);
        $newUsers = collect([$newUserOne, $newUserTwo]);
        $this->assignUsersToCompany($newUsers, $company);

        return $newUsers;
    }

    /**
     * @param $newUsers
     * @param $company
     * @param $otherCompany
     */
    private function setDataDB($newUsers, $company, $otherCompany): void
    {
        //no description
        \DB::table('user_availability')->insert([
            [
                'time_start' => '15:00:00',
                'time_stop' => '17:00:00',
                'available' => 1,
                'overtime' => 1,
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'overtime' => 0,
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
        ]);
        //overtime by description
        \DB::table('user_availability')->insert([
            [
                'time_start' => '15:00:00',
                'time_stop' => '17:00:00',
                'available' => 1,
                'description' => 'overtime',
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->subDays(1)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
        ]);
        \DB::table('user_availability')->insert([
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'overtime' => 0,
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->format('Y-m-d'),
                'company_id' => $company->id,
            ],

            //user 0 day_off
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->subDays(4)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->subDays(5)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->subMonth()->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            // sub year
            [
                'time_start' => '08:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Working',
                'overtime' => 0,
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->subYear()->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            // not current data different company
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Different company',
                'overtime' => 0,
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->format('Y-m-d'),
                'company_id' => $otherCompany->id,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '17:00:00',
                'available' => 1,
                'description' => 'overtime',
                'overtime' => 0,
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->format('Y-m-d'),
                'company_id' => $otherCompany->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[0]->id,
                'day' => with(clone $this->start)->subDays(2)->format('Y-m-d'),
                'company_id' => $otherCompany->id,
            ],
            //--------------------------------
            [
                'time_start' => '10:00:00',
                'time_stop' => '13:00:00',
                'available' => 1,
                'description' => 'overtime',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '03:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '15:30:00',
                'available' => 1,
                'description' => 'overtime',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '01:00:00',
                'available' => 1,
                'description' => 'Sample description test',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subMonth()->subDays(4)->format('Y-m-d'),
                'company_id' => $company->id,
            ],

            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subMonth()->subDays(3)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subMonth()->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subMonths(5)->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'hollyday',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subMonths(5)->subDays(3)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subMonths(5)->subDays(2)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subMonths(7)->subDays(10)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'day_off',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subYear()->subDays(10)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '00:00:00',
                'time_stop' => '00:00:00',
                'available' => 0,
                'description' => 'hollyday',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subYear()->subDays(3)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '08:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Working',
                'overtime' => 0,
                'user_id' => $newUsers[1]->id,
                'day' => with(clone $this->start)->subYear()->format('Y-m-d'),
                'company_id' => $company->id,
            ],
            [
                'time_start' => '08:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'description' => 'Working',
                'overtime' => 0,
                'user_id' => $this->user->id,
                'day' => with(clone $this->start)->format('Y-m-d'),
                'company_id' => $company->id,
            ],
        ]);
    }

    private function createLoggedUserAndAddToCompany(string $role = RoleType::DEVELOPER, string $package = Package::PREMIUM): Company
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage($role, $package);
        auth()->loginUsingId($this->user->id);
        $this->setAuthUser($company);

        return $company;
    }

    private function sumHoursFromDays(array $days): int
    {
        return array_sum(Arr::pluck($days, 'hours'));
    }

    private function prepareWeekDays(): array
    {
        return [
            ['hours' => 6, 'date' => Carbon::parse('2023-04-03')],
            ['hours' => 8, 'date' => Carbon::parse('2023-04-12')],
            ['hours' => 7, 'date' => Carbon::parse('2023-04-14')],
        ];
    }

    private function prepareSaturdays(): array
    {
        return [
            ['hours' => 6, 'date' => Carbon::parse('2023-04-01')],
            ['hours' => 8, 'date' => Carbon::parse('2023-04-08')],
        ];
    }

    private function prepareSundays(): array
    {
        return [
            ['hours' => 9, 'date' => Carbon::parse('2023-04-02')],
            ['hours' => 4, 'date' => Carbon::parse('2023-04-09')],
        ];
    }


    private function prepareWeekendDays(): array
    {
        return [
            ['hours' => 4, 'date' => Carbon::parse('2023-04-01')],
            ['hours' => 6, 'date' => Carbon::parse('2023-04-02')],
            ['hours' => 9, 'date' => Carbon::parse('2023-04-15')],
        ];
    }

    private function insertUserWorkingDays(User $user, Company $company, array $days, bool $overtime=false)
    {
        foreach ($days as $day) {
            $this->insertUserWorkingHoursForDay($user, $company, $day['hours'], $day['date'], $overtime);
        }
    }

    private function insertUserWorkingHoursForDay(User $user, Company $company, int $hours, Carbon $day, bool $overtime)
    {
        $day->setTime(12, 0);

        \DB::table('user_availability')->insert([
            'time_start' => $day->toTimeString(),
            'time_stop' => (clone $day)->addHours($hours)->toTimeString(),
            'available' => 1,
            'overtime' => $overtime,
            'user_id' => $user->id,
            'day' => $day->toDateString(),
            'company_id' => $company->id,
        ]);
    }
}
