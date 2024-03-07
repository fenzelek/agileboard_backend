<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityManager;

use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\CalendarAvailability\Services\AvailabilityFactory\StoreOwnAvailabilityManagerFactory;
use App\Modules\CalendarAvailability\Services\AvailabilityManager;
use App\Notifications\OvertimeAdded;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\AvailabilityToolsTrait;

class AvailabilityManagerDeveloperTest extends TestCase
{
    use DatabaseTransactions;
    use AvailabilityToolsTrait;
    /**
     * @var AvailabilityManager
     */
    private AvailabilityManager $availability_manager;

    /**
     * @var Company
     */
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();

        auth()->loginUsingId($this->user->id);
        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        $this->user->setSelectedCompany($this->company->id);

        $this->admins = factory(User::class, 1)->create(['deleted' => 0]);
        $this->assignUsersToCompany($this->admins, $this->company, RoleType::ADMIN);
        $this->owners = factory(User::class, 1)->create(['deleted' => 0]);
        $this->assignUsersToCompany($this->owners, $this->company, RoleType::OWNER);

        $this->other_user = factory(User::class)->create(['deleted' => 0]);

        $store_own_availability = $this->app->make(StoreOwnAvailabilityManagerFactory::class);
        $this->availability_manager = $store_own_availability->create($this->user);
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Success add developer availability, overtime false
     *
     * @test
     * @throws \App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability
     */
    public function success_add_developer_availability()
    {
        //GIVEN
        Notification::fake();
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
        $response = $this->availability_manager->storeAvailability($availability_provider, $this->user);

        //THEN
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(2, $response);
        Notification::assertNotSentTo($this->admins[0], OvertimeAdded::class);
        Notification::assertNotSentTo($this->owners[0], OvertimeAdded::class);
        Notification::assertNotSentTo($this->user, OvertimeAdded::class);
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Success add developer availability, overtime true
     *
     * @test
     * @throws \App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability
     */
    public function success_add_developer_availability_overtime()
    {
        //GIVEN
        $this->markTestSkipped('Commented by Dominic`s decision 6.04.22');
        Notification::fake();
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
                'overtime' => true,
            ],
        ];

        $availabilities = $this->toUserAvailability($rowAvailabilities);
        $availability_provider = $this->getRequest($availabilities, $day, $this->company->id);

        //WHEN
        $response = $this->availability_manager->storeAvailability($availability_provider, $this->user);

        //THEN
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(2, $response);
        Notification::assertSentTo(
            $this->owners[0],
            OvertimeAdded::class,
            function ($notification, $channels) {
                return $channels == ['mail'];
            }
        );
        Notification::assertSentTo(
            $this->admins[0],
            OvertimeAdded::class,
            function ($notification, $channels) {
                return $channels == ['mail'];
            }
        );
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Success add develop availability, overtime true
     *
     * @test
     * @throws \App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability
     */
    public function success_add_developer_availability_time_cover_time_in_DB()
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
                'overtime' => false,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '16:30:00',
                'available' => true,
                'description' => 'This is overtime',
                'overtime' => true,
            ],
        ];

        $availabilities = $this->toUserAvailability($rowAvailabilities);
        $availability_provider = $this->getRequest($availabilities, $day, $this->company->id);

        //WHEN
        $response = $this->availability_manager->storeAvailability($availability_provider, $this->user);

        //THEN
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(3, $response);
        $this->assertEquals($availabilities[1]->getStartTime(), $response->get(1)->time_start);
        $this->assertEquals($availabilities[1]->getStopTime(), $response->get(1)->time_stop);
    }
}
