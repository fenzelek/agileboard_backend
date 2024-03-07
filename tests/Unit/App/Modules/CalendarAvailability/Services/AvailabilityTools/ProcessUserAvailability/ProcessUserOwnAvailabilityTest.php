<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\ProcessUserAvailability;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\ProcessUserOwnAvailability;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\AvailabilityToolsTrait;

class ProcessUserOwnAvailabilityTest extends TestCase
{
    use DatabaseTransactions;
    use AvailabilityToolsTrait;

    /**
     * @var Company
     */
    private Company $company;
    /**
     * @var User
     */
    private User $other_user;
    /**
     * @var ProcessUserOwnAvailability
     */
    private ProcessUserOwnAvailability $process_availability;

    public function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->user->setSelectedCompany($this->company->id);

        $this->other_user = factory(User::class)->create();

        $this->process_availability =
            $this->app->make(
                ProcessUserOwnAvailability::class,
                ['user' => $this->user]
            );
    }

    /**
     * @feature User Availability
     * @scenario Process availabilities
     * @case Success add developer availability
     *
     * @test
     */
    public function process_availability_when_user_developer()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $newAvailability = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '12:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '12:00:00',
                'time_stop' => '16:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
        ];
        $availabilities = $this->toUserAvailability($newAvailability);
        $calendar_availability_store =
            $this->getRequest($availabilities, now()->format('Y-m-d'), $this->company->id);

        //WHEN
        $response = $this->process_availability->processAvailability($calendar_availability_store);

        //THEN
        $this->assertEquals($newAvailability[1]['time_start'], $response->getAvailability()->first()->getStartTime());
        $this->assertEquals($newAvailability[1]['time_stop'], $response->getAvailability()->first()->getStopTime());
        $this->assertDatabaseCount(UserAvailability::class, 10);
    }

    /**
     * @feature User Availability
     * @scenario Process availabilities
     * @case Failed add developer availability
     *
     * @test
     */
    public function process_availability_when_user_developer_time_cover_time_DB()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $newAvailability = [
            [
                'time_start' => '07:00:00',
                'time_stop' => '11:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
        ];
        $availabilities = $this->toUserAvailability($newAvailability);
        $calendar_availability_store =
            $this->getRequest($availabilities, now()->format('Y-m-d'), $this->company->id);

        //WHEN
        $response = $this->process_availability->processAvailability($calendar_availability_store);

        //THEN
        $this->assertIsObject($response->getAvailability());
        $this->assertEmpty($response->getAvailability()->all());
        $this->assertDatabaseCount(UserAvailability::class, 10);
    }

    /**
     * @feature User Availability
     * @scenario Process availabilities
     * @case Failed add developer availability
     *
     * @test
     */
    public function process_availability_when_user_developer_time_inside_time_DB()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $newAvailability = [
            [
                'time_start' => '08:01:00',
                'time_stop' => '09:59:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
        ];
        $availabilities = $this->toUserAvailability($newAvailability);
        $calendar_availability_store =
            $this->getRequest($availabilities, now()->format('Y-m-d'), $this->company->id);

        //WHEN
        $response = $this->process_availability->processAvailability($calendar_availability_store);

        //THEN
        $this->assertIsObject($response->getAvailability());
        $this->assertEmpty($response->getAvailability()->all());
        $this->assertDatabaseCount(UserAvailability::class, 10);
    }

    /**
     * @feature User Availability
     * @scenario Process availabilities
     * @case Failed add developer availability, cover left side
     *
     * @test
     */
    public function process_availability_when_user_developer_time_cover_time_DB_left_side()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $newAvailability = [
            [
                'time_start' => '07:00:00',
                'time_stop' => '09:59:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
        ];
        $availabilities = $this->toUserAvailability($newAvailability);
        $calendar_availability_store =
            $this->getRequest($availabilities, now()->format('Y-m-d'), $this->company->id);

        //WHEN
        $response = $this->process_availability->processAvailability($calendar_availability_store);

        //THEN
        $this->assertIsObject($response->getAvailability());
        $this->assertEmpty($response->getAvailability()->all());
        $this->assertDatabaseCount(UserAvailability::class, 10);
    }

    /**
     * @feature User Availability
     * @scenario Process availabilities
     * @case Failed add developer availability, cover left side
     *
     * @test
     */
    public function process_availability_when_user_developer_time_cover_time_DB_right_side()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $newAvailability = [
            [
                'time_start' => '08:01:00',
                'time_stop' => '10:30:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
        ];
        $availabilities = $this->toUserAvailability($newAvailability);
        $calendar_availability_store =
            $this->getRequest($availabilities, now()->format('Y-m-d'), $this->company->id);

        //WHEN
        $response = $this->process_availability->processAvailability($calendar_availability_store);

        //THEN
        $this->assertIsObject($response->getAvailability());
        $this->assertEmpty($response->getAvailability()->all());
        $this->assertDatabaseCount(UserAvailability::class, 10);
    }

    /**
     * @feature User Availability
     * @scenario Process availabilities
     * @case Success add developer availability, next day
     *
     * @test
     */
    public function process_availability_when_user_developer_next_day()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $newAvailability = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '12:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '12:00:00',
                'time_stop' => '16:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
        ];
        $availabilities = $this->toUserAvailability($newAvailability);
        $calendar_availability_store =
            $this->getRequest(
                $availabilities,
                now()->addDay()->format('Y-m-d'),
                $this->company->id
            );

        //WHEN
        $response = $this->process_availability->processAvailability($calendar_availability_store);

        //THEN
        $this->assertEquals($newAvailability[0]['time_start'], $response->getAvailability()->first()->getStartTime());
        $this->assertEquals($newAvailability[0]['time_stop'], $response->getAvailability()->first()->getStopTime());
        $this->assertDatabaseCount(UserAvailability::class, 10);
    }
}
