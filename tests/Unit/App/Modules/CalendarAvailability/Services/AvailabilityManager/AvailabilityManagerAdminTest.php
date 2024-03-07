<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityManager;

use App\Models\Db\Package;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Models\Other\RoleType;
use App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityFactory\StoreAvailabilityManagerFactory;
use App\Notifications\OvertimeAdded;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\AvailabilityToolsTrait;

class AvailabilityManagerAdminTest extends TestCase
{
    use DatabaseTransactions;
    use AvailabilityToolsTrait;

    const CONFIRMED = 'CONFIRMED';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();

        auth()->loginUsingId($this->user->id);
        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        $this->user->setSelectedCompany($this->company->id);

        $users = factory(User::class, 1)->create(['deleted' => 0]);

        $this->assignUsersToCompany($users, $this->company, RoleType::DEVELOPER);
        $this->other_user = $users->first();

        $store_own_availability = $this->app->make(StoreAvailabilityManagerFactory::class);
        $this->availability_manager = $store_own_availability->create($this->other_user);
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Success add developer availability, by admin
     *
     * @test
     * @throws \App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability
     */
    public function success_add_for_developer_availability_when_empty_DB()
    {
        //GIVEN
        $day = Carbon::now()->format('Y-m-d');
        $rowAvailabilities = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '16:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '17:00:00',
                'available' => true,
                'description' => 'This is overtime',
                'overtime' => false,
            ],
        ];
        $availabilities = $this->toUserAvailability($rowAvailabilities);
        $availability_provider = $this->getRequest($availabilities, $day, $this->company->id);

        //WHEN
        $response = $this->availability_manager->storeAvailability(
            $availability_provider,
            $this->other_user
        );

        //THEN
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(2, $response);
        $this->assertDatabaseHas(
            UserAvailability::class,
            array_merge([
                'overtime' => $availabilities[0]->getOvertime(),
                'time_start' => $availabilities[0]->getStartTime(),
                'time_stop' => $availabilities[0]->getStopTime(),
                'user_id' => $this->other_user->id,
                'company_id' => $this->company->id,
                'status' => self::CONFIRMED,
            ])
        );
        $this->assertDatabaseHas(
            UserAvailability::class,
            array_merge([
                'overtime' => $availabilities[1]->getOvertime(),
                'time_start' => $availabilities[1]->getStartTime(),
                'time_stop' => $availabilities[1]->getStopTime(),
                'user_id' => $this->other_user->id,
                'company_id' => $this->company->id,
                'status' => self::CONFIRMED,
            ])
        );
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Success add one period for developer, by admin
     *
     * @test
     * @throws \App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability
     */
    public function success_add_one_time_period_for_developer_availability()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $day = Carbon::now()->format('Y-m-d');
        $rowAvailabilities = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '16:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '17:00:00',
                'available' => true,
                'description' => 'This is overtime',
                'overtime' => false,
            ],
        ];
        $availabilities = $this->toUserAvailability($rowAvailabilities);
        $availability_provider = $this->getRequest($availabilities, $day, $this->company->id);

        //WHEN
        $response = $this->availability_manager->storeAvailability(
            $availability_provider,
            $this->other_user
        );

        //THEN
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(2, $response);
        $this->assertDatabaseHas(
            UserAvailability::class,
            array_merge([
                'overtime' => $availabilities[0]->getOvertime(),
                'time_start' => $availabilities[0]->getStartTime(),
                'time_stop' => $availabilities[0]->getStopTime(),
                'user_id' => $this->other_user->id,
                'company_id' => $this->company->id,
                'status' => self::CONFIRMED,
            ])
        );
        $this->assertDatabaseHas(
            UserAvailability::class,
            array_merge([
                'overtime' => $availabilities[1]->getOvertime(),
                'time_start' => $availabilities[1]->getStartTime(),
                'time_stop' => $availabilities[1]->getStopTime(),
                'user_id' => $this->other_user->id,
                'company_id' => $this->company->id,
                'status' => self::CONFIRMED,
            ])
        );
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Success add one period for developer (overtime), by admin
     *
     * @test
     * @throws \App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability
     */
    public function success_add_one_time_period_for_developer_availability_overtime()
    {
        //GIVEN
        Notification::fake();
        $this->setDataAvailabilityInDB();
        $day = Carbon::now()->format('Y-m-d');
        $rowAvailabilities = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '16:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => true,
            ],
        ];
        $availabilities = $this->toUserAvailability($rowAvailabilities);
        $availability_provider = $this->getRequest($availabilities, $day, $this->company->id);

        //WHEN
        $response = $this->availability_manager->storeAvailability(
            $availability_provider,
            $this->other_user
        );

        //THEN
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(1, $response);
        $this->assertDatabaseHas(
            UserAvailability::class,
            array_merge([
                'overtime' => $availabilities[0]->getOvertime(),
                'time_start' => $availabilities[0]->getStartTime(),
                'time_stop' => $availabilities[0]->getStopTime(),
                'user_id' => $this->other_user->id,
                'company_id' => $this->company->id,
                'status' => self::CONFIRMED,
            ])
        );
        Notification::assertNotSentTo($this->other_user, OvertimeAdded::class);
        Notification::assertNotSentTo($this->user, OvertimeAdded::class);
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Failed add one period for developer (overtime), by admin
     *
     * @test
     * @throws \App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability
     */
    public function failed_add_one_time_period_for_developer_wrong_time()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $day = Carbon::now()->format('Y-m-d');
        $rowAvailabilities = [
            [
                'time_start' => '16:00:00',
                'time_stop' => '01:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => true,
            ],
        ];
        $availabilities = $this->toUserAvailability($rowAvailabilities);
        $availability_provider = $this->getRequest($availabilities, $day, $this->company->id);

        //WHEN
        try {
            $this->availability_manager->storeAvailability(
                $availability_provider,
                $this->other_user
            );
        } catch (InvalidTimePeriodAvailability $e) {
            //THEN
            $this->assertInstanceOf(InvalidTimePeriodAvailability::class, $e);
        }
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Failed add two period for developer (overtime), by admin
     *
     * @test
     * @throws \App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability
     */
    public function failed_add_one_time_period_for_developer_wrong_periods()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $day = Carbon::now()->format('Y-m-d');
        $rowAvailabilities = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '16:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '12:00:00',
                'time_stop' => '19:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => true,
            ],
        ];
        $availabilities = $this->toUserAvailability($rowAvailabilities);
        $availability_provider = $this->getRequest($availabilities, $day, $this->company->id);

        //WHEN
        try {
            $this->availability_manager->storeAvailability(
                $availability_provider,
                $this->other_user
            );
        } catch (InvalidTimePeriodAvailability $e) {
            //THEN
            $this->assertInstanceOf(InvalidTimePeriodAvailability::class, $e);
        }
    }
}
