<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityManager\DaysOffManager;

use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\CalendarAvailability\Contracts\AddDaysOffInterface;
use App\Modules\CalendarAvailability\Models\DayOffDTO;
use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;
use App\Modules\CalendarAvailability\Services\AvailabilityFactory\StoreOwnAvailabilityManagerFactory;
use App\Modules\CalendarAvailability\Services\AvailabilityManager;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\AdderAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\DestroyerAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\ProcessUserAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\DaysOffValidator;
use App\Modules\CalendarAvailability\Services\DaysOffManager;
use App\Notifications\OvertimeAdded;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DaysOffManagerTest extends TestCase
{
    use DatabaseTransactions;
    use DaysOffManagerTrait;

    private DaysOffManager $availability_manager;

    /**
     * @var Company
     */
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->other_user = \Mockery::mock(User::class);

        $this->validator = \Mockery::mock(DaysOffValidator::class);
        $this->destroyer_availability = \Mockery::mock(DestroyerAvailability::class);
        $this->adder_availability = \Mockery::mock(AdderAvailability::class);

        $this->availability_manager = $this->app->make(DaysOffManager::class, [
            'validator' => $this->validator,
            'destroyer_availability' => $this->destroyer_availability,
            'adder_availability' => $this->adder_availability,
        ]);
    }

    /**
     * @feature Calendar
     * @scenario Add Days Off
     * @case Failed availability is incorrect
     *
     * @test
     */
    public function add_whenDatesWasPast_failed()
    {
        //GIVEN
        $day = new DayOffDTO('2010-01-01','sample');
        $availability_provider = $this->makeDaysOffProvider($day);

        //WHEN

        $validator_expectation = $this->validator->shouldReceive('validate')
            ->andReturn(true);
        $destroyer_expectation = $this->destroyer_availability->shouldReceive('destroy');
        $adder_expectation = $this->adder_availability->shouldReceive('add');

        $response = $this->availability_manager->storeAvailability(
            $availability_provider,
        );

        //THEN
        $validator_expectation->once();
        $destroyer_expectation->once();
        $adder_expectation->once();
    }

    private function makeDaysOffProvider(DayOffDTO $day_off):AddDaysOffInterface
    {
        return new class($day_off) implements AddDaysOffInterface{

            private $day_off;

            public function __construct($day_off)
            {
                $this->day_off = $day_off;
            }

            public function getSelectedCompanyId(): int
            {
                return 1;
            }

            public function getUserId(): int
            {
                return 1;
            }

            public function getDays(): array
            {
                return [$this->day_off];
            }
        };
    }

}
