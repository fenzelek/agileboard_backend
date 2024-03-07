<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\ValidatorAvailability;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\ServeAvailabilityValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\AvailabilityToolsTrait;

class ServeAvailabilityValidatorTest extends TestCase
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
     * @var ServeAvailabilityValidator
     */
    private ServeAvailabilityValidator $availability_validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->user->setSelectedCompany($this->company->id);

        $this->other_user = factory(User::class)->create();

        $this->availability_validator =
            $this->app->make(
                ServeAvailabilityValidator::class,
                ['user' => $this->user]
            );
    }

    /**
     * @feature User Availability
     * @scenario Validate availabilities
     * @case Success availability is correct
     *
     * @test
     */
    public function success_availability_correct()
    {
        //GIVEN
        $newAvailability = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '12:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '17:00:00',
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
        $response = $this->availability_validator->validate($calendar_availability_store);
        //THEN
        $this->assertTrue($response);
    }

    /**
     * @feature User Availability
     * @scenario Validate availabilities
     * @case Success availability is not correct second time cover stop time
     *
     * @test
     */
    public function success_availability_not_correct_second_time_overlap_to_stop_time()
    {
        //GIVEN
        $newAvailability = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '12:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '11:00:00',
                'time_stop' => '17:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
        ];
        $availabilities = $this->toUserAvailability($newAvailability);

        $calendar_availability_store =
            $this->getRequest($availabilities, now()->format('Y-m-d'), $this->company->id);

        //WHEN
        $response = $this->availability_validator->validate($calendar_availability_store);
        //THEN
        $this->assertFalse($response);
    }

    /**
     * @feature User Availability
     * @scenario Validate availabilities
     * @case Failed availability is not correct second time cover start time
     *
     * @test
     */
    public function success_availability_not_correct_second_time_overlap_to_start_time()
    {
        //GIVEN
        $newAvailability = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '12:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '06:00:00',
                'time_stop' => '10:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
        ];
        $availabilities = $this->toUserAvailability($newAvailability);

        $calendar_availability_store =
            $this->getRequest($availabilities, now()->format('Y-m-d'), $this->company->id);

        //WHEN
        $response = $this->availability_validator->validate($calendar_availability_store);
        //THEN
        $this->assertFalse($response);
    }

    /**
     * @feature User Availability
     * @scenario Validate availabilities
     * @case Failed availability is correct not correct second time cover
     *
     * @test
     */
    public function success_availability_not_correct_second_time_cover_first_time()
    {
        //GIVEN
        $newAvailability = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '12:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '06:00:00',
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
        $response = $this->availability_validator->validate($calendar_availability_store);
        //THEN
        $this->assertFalse($response);
    }

    /**
     * @feature User Availability
     * @scenario Validate availabilities
     * @case Failed availability different days given
     *
     * @test
     */
    public function failed_availability_time_different_days()
    {
        //GIVEN
        $newAvailability = [
            [
                'time_start' => '08:00:00',
                'time_stop' => '12:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '19:00:00',
                'time_stop' => '12:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
        ];

        $availabilities = $this->toUserAvailability($newAvailability);

        $calendar_availability_store =
            $this->getRequest($availabilities, now()->format('Y-m-d'), $this->company->id);

        //WHEN
        $response = $this->availability_validator->validate($calendar_availability_store);

        //THEN
        $this->assertFalse($response);
    }

    /**
     * @feature User Availability
     * @scenario Validate availabilities
     * @case Failed availability different days given
     *
     * @test
     */
    public function failed_availability_intersect_time()
    {
        //GIVEN
        $newAvailability = [
            [
                'time_start' => '06:00:00',
                'time_stop' => '09:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '09:30:00',
                'time_stop' => '11:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],
            [
                'time_start' => '09:00:00',
                'time_stop' => '10:00:00',
                'available' => true,
                'description' => 'This is my usually work time',
                'overtime' => false,
            ],

        ];
        $availabilities = $this->toUserAvailability($newAvailability);
        $calendar_availability_store =
            $this->getRequest($availabilities, now()->format('Y-m-d'), $this->company->id);

        //WHEN
        $response = $this->availability_validator->validate($calendar_availability_store);

        //THEN
        $this->assertFalse($response);
    }
}
