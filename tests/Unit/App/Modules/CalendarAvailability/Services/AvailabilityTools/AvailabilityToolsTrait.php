<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools;

use App\Models\Db\User;
use App\Modules\CalendarAvailability\Contracts\AvailabilityStore;
use App\Modules\CalendarAvailability\Contracts\UserAvailabilityInterface;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityStoreOwn;
use App\Modules\CalendarAvailability\Http\Requests\UserAvailability;
use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;
use Illuminate\Support\Collection;

trait AvailabilityToolsTrait
{
    protected function getNewAvailabilities()
    {
        $newAvailabilities = [
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
        $availabilities = $this->toUserAvailability($newAvailabilities);

        return new ProcessListAvailabilityDTO($availabilities);
    }

    protected function setDataAvailabilityInDB()
    {
        \DB::table('user_availability')->insert([
            [
                'time_start' => '08:00:00',
                'time_stop' => '10:00:00',
                'available' => 1,
                'overtime' => 0,
                'description' => 'This is my usually work time',
                'status' => 'CONFIRMED',
                'user_id' => $this->user->id,
                'day' => now()->format('Y-m-d'),
                'company_id' => $this->company->id,
            ],
            [
                'time_start' => '10:00:00',
                'time_stop' => '16:00:00',
                'available' => 1,
                'overtime' => 0,
                'description' => 'This is my usually work time',
                'status' => 'ADDED',
                'user_id' => $this->user->id,
                'day' => now()->format('Y-m-d'),
                'company_id' => $this->company->id,
            ],
            [
                'time_start' => '16:00:00',
                'time_stop' => '17:00:00',
                'available' => 0,
                'overtime' => 0,
                'description' => 'This is my rest time',
                'status' => 'ADDED',
                'user_id' => $this->user->id,
                'day' => now()->format('Y-m-d'),
                'company_id' => $this->company->id,
            ],
            [
                'time_start' => '17:00:00',
                'time_stop' => '19:00:00',
                'available' => 1,
                'overtime' => 1,
                'description' => 'This is my overtime',
                'status' => 'CONFIRMED',
                'user_id' => $this->user->id,
                'day' => now()->format('Y-m-d'),
                'company_id' => $this->company->id,
            ],
            [
                'time_start' => '07:00:00',
                'time_stop' => '15:00:00',
                'available' => 1,
                'overtime' => 0,
                'description' => 'This is my work time, but tomorrow',
                'status' => 'ADDED',
                'user_id' => $this->user->id,
                'day' => now()->addDay()->format('Y-m-d'),
                'company_id' => $this->company->id,
            ],
            [
                'time_start' => '15:00:00',
                'time_stop' => '17:00:00',
                'available' => 1,
                'overtime' => 1,
                'description' => 'This is my overtime time, but tomorrow',
                'status' => 'CONFIRMED',
                'user_id' => $this->user->id,
                'day' => now()->addDay()->format('Y-m-d'),
                'company_id' => $this->company->id,
            ],
            [
                'time_start' => '07:00:00',
                'time_stop' => '12:00:00',
                'available' => 1,
                'overtime' => 0,
                'description' => 'This is NOT my work time',
                'status' => 'CONFIRMED',
                'user_id' => $this->other_user->id,
                'day' => now()->format('Y-m-d'),
                'company_id' => $this->company->id,
            ],
            [
                'time_start' => '12:00:00',
                'time_stop' => '15:00:00',
                'available' => 1,
                'overtime' => 0,
                'description' => 'This is NOT my work time, but tomorrow',
                'status' => 'ADDED',
                'user_id' => $this->other_user->id,
                'day' => now()->format('Y-m-d'),
                'company_id' => $this->company->id,
            ],
            [
                'time_start' => '07:00:00',
                'time_stop' => '12:00:00',
                'available' => 1,
                'overtime' => 0,
                'description' => 'This is NOT my work time',
                'status' => 'CONFIRMED',
                'user_id' => $this->other_user->id,
                'day' => now()->addDay()->format('Y-m-d'),
                'company_id' => $this->company->id,
            ],
            [
                'time_start' => '12:00:00',
                'time_stop' => '15:00:00',
                'available' => 1,
                'overtime' => 0,
                'description' => 'This is NOT my work time, but tomorrow',
                'status' => 'ADDED',
                'user_id' => $this->other_user->id,
                'day' => now()->addDay()->format('Y-m-d'),
                'company_id' => $this->company->id,
            ],
        ]);
    }

    protected function assertDBHas(int $count, string $day, User $user): void
    {
        $this->assertEquals($count, \DB::table('user_availability')
            ->where('user_id', $user->id)
            ->where('day', $day)
            ->where('company_id', $this->company->id)
            ->count());
    }

    protected function makeUserAvailability(
        string $start_time,
        string $stop_time,
        string $description,
        bool $overtime,
        bool $available,
        string $source
    ) {
        $availability = \Mockery::mock(UserAvailabilityInterface::class);
        $availability->shouldReceive('getStartTime')->once()->andReturn($start_time);
        $availability->shouldReceive('getStopTime')->once()->andReturn($stop_time);
        $availability->shouldReceive('getDescription')->once()->andReturn($description);
        $availability->shouldReceive('getOvertime')->once()->andReturn($overtime);
        $availability->shouldReceive('getAvailable')->once()->andReturn($available);
        $availability->shouldReceive('getSource')->once()->andReturn($source);
        $mock = \Mockery::mock(ProcessListAvailabilityDTO::class);
        $mock->shouldReceive('getAvailability')->once()->andReturn(Collection::make([$availability]));
        return $mock;
    }

    private function getRequest($newAvailability, $day, $company_id)
    {
        $calendar_availability_store = \Mockery::mock(CalendarAvailabilityStoreOwn::class);
        $calendar_availability_store->shouldReceive('getDay')->andReturn($day);
        $calendar_availability_store->shouldReceive('getAvailabilities')
            ->andReturn($newAvailability);
        $calendar_availability_store->shouldReceive('getCompanyId')->andReturn($company_id);
        $this->instance(AvailabilityStore::class, $calendar_availability_store);

        return $calendar_availability_store;
    }

    private function toUserAvailability(array $newAvailability): array
    {
        return collect($newAvailability)->map(function (array $newAvailability) {
            return new UserAvailability($newAvailability);
        })->all();
    }
}
