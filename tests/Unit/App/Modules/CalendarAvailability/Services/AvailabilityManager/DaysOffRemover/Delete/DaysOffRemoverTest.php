<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityManager\DaysOffRemover\Delete;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Modules\CalendarAvailability\Contracts\DeleteDaysOffInterface;
use App\Modules\CalendarAvailability\Models\UserAvailabilitySourceType;
use App\Modules\CalendarAvailability\Services\DaysOffRemover;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DaysOffRemoverTest extends TestCase
{
    use DatabaseTransactions;
    use DaysOffRemoverTrait;

    private DaysOffRemover $availability_manager;

    /**
     * @var Company
     */
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->other_user = \Mockery::mock(User::class);

        $this->availability_manager = $this->app->make(DaysOffRemover::class, []);
    }

    /**
     * @feature Calendar
     * @scenario Delete Days Off
     * @case Failed because availability is internal added
     *
     * @test
     */
    public function delete_whenDaysOffEnternalAdded_notDeleted()
    {
        //GIVEN
        $date = '2010-01-01';
        $day_off_dto = $this->makeDaysOffProvider(Carbon::parse($date));
        $this->createDayOff($date, UserAvailabilitySourceType::INTERNAL, $day_off_dto->getSelectedCompanyId(), $day_off_dto->getUserId());
        //WHEN

        $this->availability_manager->delete($day_off_dto);

        //THEN
        $this->assertDatabaseHas('user_availability', [
                'available' => 0,
                'overtime' => 0,
                'description' => '',
                'status' => 'CONFIRMED',
                'user_id' => $day_off_dto->getUserId(),
                'day' => $date,
                'company_id' => $day_off_dto->getSelectedCompanyId(),
                'source' => UserAvailabilitySourceType::INTERNAL
            ]);
    }


    /**
     * @feature Calendar
     * @scenario Delete Days Off
     * @case Seccussful deleted
     *
     * @test
     */
    public function delete_successfulDeleted()
    {
        //GIVEN
        $date = '2010-01-01';
        $day_off_dto = $this->makeDaysOffProvider(Carbon::parse($date));
        $this->createDayOff($date, UserAvailabilitySourceType::EXTERNAL, $day_off_dto->getSelectedCompanyId(), $day_off_dto->getUserId());
        //WHEN

        $this->availability_manager->delete($day_off_dto);

        //THEN
        $this->assertDatabaseMissing('user_availability', [
            'user_id' => $day_off_dto->getUserId(),
            'day' => $date,
            'company_id' => $day_off_dto->getSelectedCompanyId(),
            'source' => UserAvailabilitySourceType::EXTERNAL
        ]);
    }

    private function makeDaysOffProvider(CarbonInterface $day_off):DeleteDaysOffInterface
    {
        return new class($day_off) implements DeleteDaysOffInterface{

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

    private function createDayOff(string $date, string $source, int $user_id, int $company_id)
    {
        \DB::table('user_availability')->insert([
            [
                'time_start' => '08:00:00',
                'time_stop' => '10:00:00',
                'available' => 0,
                'overtime' => 0,
                'description' => '',
                'status' => 'CONFIRMED',
                'user_id' => $user_id,
                'day' => $date,
                'company_id' => $company_id,
                'source' => $source
            ]
        ]);
    }

}
