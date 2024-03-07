<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\DaysOffValidator;

use App\Modules\CalendarAvailability\Models\DayOffDTO;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\DaysOffValidator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DaysOffValidatorTest extends TestCase
{
    use DatabaseTransactions;
    use DaysOffValidatorTrait;

    private DaysOffValidator $availability_validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->availability_validator = $this->app->make(DaysOffValidator::class);
    }

    /**
     * @feature Calendar
     * @scenario Add Days Off
     * @case Failed availability is incorrect
     *
     * @test
     * @expectation DayOff was from past
     *
     * @dataProvider providePastDates
     */
    public function validate_incomingDayOffIsPast(string $past_date)
    {
        //GIVEN
        $day_off = new DayOffDTO($past_date, 'outdated');

        //WHEN
        $response = $this->availability_validator->validate([$day_off]);
        //THEN
        $this->assertFalse($response);
    }


    /**
     * @feature Calendar
     * @scenario Add Days Off
     * @case Success availability is correct
     *
     * @test
     * @expectation DayOff was from Future
     * @dataProvider provideFutureDates
     */
    public function validate_incomingDayOffIsFuture(string $future_date)
    {
        //GIVEN
        $day_off = new DayOffDTO($future_date, 'future');

        //WHEN
        $response = $this->availability_validator->validate([$day_off]);
        //THEN
        $this->assertTrue($response);
    }

    public function providePastDates()
    {
        return [
            ['long past' => '2010-01-01'],
            ['today' => Carbon::yesterday()->subMonth()->subDay()->format('Y-m-d')],
        ];
    }


    public function provideFutureDates()
    {
        return [
            ['tomorrow' => Carbon::today()->subMonth()->format('Y-m-d')],
            ['long future' => Carbon::today()->addYear()->format('Y-m-d')],
        ];
    }
}
