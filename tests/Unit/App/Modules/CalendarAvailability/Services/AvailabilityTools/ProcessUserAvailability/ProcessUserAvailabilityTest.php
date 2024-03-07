<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\ProcessUserAvailability;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Contracts\AvailabilityStore;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityStore;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\ProcessUserAvailability;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\AvailabilityToolsTrait;

class ProcessUserAvailabilityTest extends TestCase
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
     * @var ProcessUserAvailability
     */
    private ProcessUserAvailability $process_availability;

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
                ProcessUserAvailability::class,
                ['user' => $this->other_user]
            );
    }

    /**
     * @feature User Availability
     * @scenario Process availabilities
     * @case Success add admin availability
     *
     * @test
     */
    public function process_availability_when_user_admin()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $newAvailability = [
            'availabilities' => [
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
            ],
        ];

        $request = new CalendarAvailabilityStore($newAvailability);
        $this->instance(AvailabilityStore::class, $request);

        //WHEN
        $response = $this->process_availability->processAvailability($request);

        //THEN
        $this->assertEquals($request->getAvailabilities()[0], $response->getAvailability()->first());
        $this->assertEquals($request->getAvailabilities()[1], $response->getAvailability()->last());
        $this->assertDatabaseCount(UserAvailability::class, 10);
    }
}
