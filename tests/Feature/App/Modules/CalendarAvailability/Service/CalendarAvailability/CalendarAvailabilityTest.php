<?php

namespace Tests\Feature\App\Modules\CalendarAvailability\Service\CalendarAvailability;

use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\CalendarAvailability\Services\CalendarAvailability;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CalendarAvailabilityTest extends TestCase
{
    use CalendarAvailabilityTrait;
    use DatabaseTransactions;

    private Carbon $start;

    protected function setUp(): void
    {
        parent::setUp();
        $this->start = Carbon::create(2021, 11, 25);
    }

    /**
     * @feature Availability
     * @scenario find availability
     * @case Success, find by admin
     *
     * @test
     */
    public function find_success_admin()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $this->setAuthUser($company);

        $otherCompany = factory(Company::class)->create();
        $newUsers = $this->CreateAndAssignTwoUsers($company);

        $start = with(clone $this->start)->startOfMonth();
        $finish = with(clone $this->start)->endOfMonth();

        $this->setDataDB($newUsers, $company, $otherCompany);

        $tested_method = $this->app->make(CalendarAvailability::class);

        //WHEN
        $response = $tested_method->find($start, $finish);

        //THEN
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(3, $response);
    }

    /**
     * @feature Availability
     * @scenario find availability
     * @case Success, find by developer
     *
     * @test
     */
    public function find_success_developer()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $this->setAuthUser($company);

        $otherCompany = factory(Company::class)->create();
        $newUsers = factory(User::class, 2)->create(['deleted' => 0]);
        $this->assignUsersToCompany($newUsers, $company);

        $start = with(clone $this->start)->startOfMonth();
        $finish = with(clone $this->start)->endOfMonth();

        $this->setDataDB($newUsers, $company, $otherCompany);

        $tested_method = $this->app->make(CalendarAvailability::class);

        //WHEN
        $response = $tested_method->find($start, $finish);

        //THEN
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(1, $response);
        $this->assertCount(1, $response->get(0)->availabilities);
    }

    /**
     * @feature Availability
     * @scenario find availability
     * @case Success, find by admin
     *
     * @test
     */
    public function findByIds_success_admin()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $this->setAuthUser($company);

        $otherCompany = factory(Company::class)->create();
        $newUsers = $this->CreateAndAssignTwoUsers($company);

        $start = with(clone $this->start)->startOfMonth();
        $finish = with(clone $this->start)->endOfMonth();

        $entry_data = [
            $newUsers->first()->id,
            $newUsers->last()->id,
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        $tested_method = $this->app->make(CalendarAvailability::class);

        //WHEN
        $response = $tested_method->findByIds($start, $finish, $entry_data);

        //THEN
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(2, $response);
        $this->assertCount(6, $response->get(0)->availabilities);
        $this->assertCount(4, $response->get(1)->availabilities);
    }

    /**
     * @feature Availability
     * @scenario find availability
     * @case Success, find by developer
     *
     * @test
     */
    public function findByIds_success_developer()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $this->setAuthUser($company);

        $otherCompany = factory(Company::class)->create();
        $newUsers = $this->CreateAndAssignTwoUsers($company);

        $start = with(clone $this->start)->startOfMonth();
        $finish = with(clone $this->start)->endOfMonth();

        $entry_data = [
            $this->user->id,
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        $tested_method = $this->app->make(CalendarAvailability::class);

        //WHEN
        $response = $tested_method->findByIds($start, $finish, $entry_data);

        //THEN
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(1, $response);
        $this->assertCount(1, $response->get(0)->availabilities);
    }

    /**
     * @feature Availability
     * @scenario prepare availabilities
     * @case Success, prepare by admin
     *
     * @test
     */
    public function prepareDataToReport_success_admin()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $this->setAuthUser($company);

        $otherCompany = factory(Company::class)->create();
        $newUsers = $this->CreateAndAssignTwoUsers($company);

        $start = with(clone $this->start)->startOfMonth();
        $finish = with(clone $this->start)->endOfMonth();

        $entry_data = [
            $newUsers->first()->id,
            $newUsers->last()->id,
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        $tested_method = $this->app->make(CalendarAvailability::class);
        $users_availabilities = $tested_method->findByIds($start, $finish, $entry_data);

        //WHEN
        $response = $tested_method->prepareDataToReport($users_availabilities);

        //THEN
        $this->assertIsArray($response);
        $this->assertEquals('Alen', $response[0]['first_name']);
        $this->assertEquals('Bazel', $response[1]['first_name']);
        $this->assertEquals(
            7200,
            $response[0]['months'][with(clone $this->start)->month]['timestamp']
        );
        $this->assertEquals(
            14400,
            $response[0]['months'][with(clone $this->start)->month]['overtime_summary']
        );
        $this->assertCount(2, $response[0]['months'][with(clone $this->start)->month]['free_days']);
        $this->assertEquals(
            14400,
            $response[1]['months'][with(clone $this->start)->month]['timestamp']
        );
        $this->assertEquals(
            12600,
            $response[1]['months'][with(clone $this->start)->month]['overtime_summary']
        );
    }

    /**
     * @feature Availability
     * @scenario prepare availabilities
     * @case Success, prepare by developer
     *
     * @test
     */
    public function prepareDataToReport_success_developer()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $this->setAuthUser($company);

        $otherCompany = factory(Company::class)->create();
        $newUsers = $this->CreateAndAssignTwoUsers($company);

        $start = with(clone $this->start)->startOfMonth();
        $finish = with(clone $this->start)->endOfMonth();

        $entry_data = [
            $this->user->id,
        ];

        $this->setDataDB($newUsers, $company, $otherCompany);

        $tested_method = $this->app->make(CalendarAvailability::class);
        $users_availabilities = $tested_method->findByIds($start, $finish, $entry_data);

        //WHEN
        $response = $tested_method->prepareDataToReport($users_availabilities);

        //THEN
        $this->assertIsArray($response);
        $this->assertEquals($this->user->first_name, $response[0]['first_name']);
        $this->assertEquals(
            28800,
            $response[0]['months'][with(clone $this->start)->month]['timestamp']
        );
    }

    /**
     * @feature Availability
     * @scenario prepare availabilities
     * @case User has weekend availabilities
     *
     * @test
     */
    public function prepareDataToReport_success_userHasWeekendAvailabilities(): void
    {
        //GIVEN
        $company = $this->createLoggedUserAndAddToCompany();
        $saturdays = $this->prepareSaturdays();
        $sundays = $this->prepareSundays();
        $weekdays = $this->prepareWeekDays();

        $sum_week_days_seconds = $this->sumHoursFromDays($weekdays) * 3600;
        $sum_saturdays_seconds = $this->sumHoursFromDays($saturdays) * 3600;
        $sum_sundays_seconds = $this->sumHoursFromDays($sundays) * 3600;

        $this->insertUserWorkingDays($this->user, $company, $weekdays);
        $this->insertUserWorkingDays($this->user, $company, $saturdays);
        $this->insertUserWorkingDays($this->user, $company, $sundays);
        $start = Carbon::parse('2023-04-01');
        $finish = Carbon::parse('2023-04-30');

        $tested_method = $this->app->make(CalendarAvailability::class);
        $users_availabilities = $tested_method->findByIds($start, $finish, [$this->user->id]);

        //WHEN
        $response = $tested_method->prepareDataToReport($users_availabilities);

        //THEN
        $this->assertIsArray($response);
        $this->assertEquals($this->user->first_name, $response[0]['first_name']);
        $this->assertEquals(
            $sum_saturdays_seconds+$sum_sundays_seconds+$sum_week_days_seconds,
            $response[0]['months'][$start->month]['timestamp']
        );
        $this->assertEquals(
            $sum_week_days_seconds,
            $response[0]['months'][$start->month]['timestamp_week_days']
        );
        $this->assertEquals(
            $sum_saturdays_seconds,
            $response[0]['months'][$start->month]['timestamp_saturdays']
        );
        $this->assertEquals(
            $sum_sundays_seconds,
            $response[0]['months'][$start->month]['timestamp_sundays']
        );
    }

    /**
     * @feature Availability
     * @scenario prepare availabilities
     * @case User has weekend overtime availabilities
     *
     * @test
     */
    public function prepareDataToReport_success_userHasWeekendOvertimeAvailabilities(): void
    {
        //GIVEN
        $company = $this->createLoggedUserAndAddToCompany();
        $weekend_days = $this->prepareWeekendDays();
        $saturdays = $this->prepareSaturdays();
        $sundays = $this->prepareSundays();
        $weekdays = $this->prepareWeekDays();

        $sum_week_days_seconds = $this->sumHoursFromDays($weekdays) * 3600;
        $sum_saturdays_seconds = $this->sumHoursFromDays($saturdays) * 3600;
        $sum_sundays_seconds = $this->sumHoursFromDays($sundays) * 3600;

        $this->insertUserWorkingDays($this->user, $company, $weekdays, $overtime=true);
        $this->insertUserWorkingDays($this->user, $company, $saturdays, $overtime=true);
        $this->insertUserWorkingDays($this->user, $company, $sundays, $overtime=true);
        $start = Carbon::parse('2023-04-01');
        $finish = Carbon::parse('2023-04-30');

        $tested_method = $this->app->make(CalendarAvailability::class);
        $users_availabilities = $tested_method->findByIds($start, $finish, [$this->user->id]);

        //WHEN
        $response = $tested_method->prepareDataToReport($users_availabilities);

        //THEN
        $this->assertIsArray($response);
        $this->assertEquals($this->user->first_name, $response[0]['first_name']);
        $this->assertEquals(
            $sum_saturdays_seconds+$sum_sundays_seconds+$sum_week_days_seconds,
            $response[0]['months'][$start->month]['overtime_summary']
        );
        $this->assertEquals(
            $sum_week_days_seconds,
            $response[0]['months'][$start->month]['overtime_summary_week_days']
        );
        $this->assertEquals(
            $sum_saturdays_seconds,
            $response[0]['months'][$start->month]['overtime_summary_saturdays']
        );
        $this->assertEquals(
            $sum_sundays_seconds,
            $response[0]['months'][$start->month]['overtime_summary_sundays']
        );
    }
}
