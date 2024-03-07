<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\AdderAvailability;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Contracts\UserAvailabilityInterface;
use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;
use App\Modules\CalendarAvailability\Models\UserAvailabilitySourceType;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\AdderAvailability;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\AvailabilityToolsTrait;
use function auth;
use function factory;
use function now;

class AdderAvailabilityTest extends TestCase
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
     * @var AdderAvailability
     */
    private AdderAvailability $own_adder;
    /**
     * @var AdderAvailability
     */
    private AdderAvailability $admin_adder;
    const ADDED = 'ADDED';
    const CONFIRMED = 'CONFIRMED';

    public function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->user->setSelectedCompany($this->company->id);

        $this->other_user = factory(User::class)->create();

        $this->own_adder = $this->app->make(
            AdderAvailability::class,
            ['user' => $this->user, 'status' => self::ADDED]
        );

        $this->admin_adder = $this->app->make(
            AdderAvailability::class,
            ['user' => $this->other_user, 'status' => self::CONFIRMED]
        );
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Success add admin availability
     *
     * @test
     */
    public function success_add_admin_availability()
    {
        //GIVEN
        $newAvailabilities = $this->getNewAvailabilities();

        //WHEN
        $this->admin_adder->add($newAvailabilities, $this->company->id, now());

        //THEN
        $this->assertDatabaseHas(
            UserAvailability::class,
            array_merge([
                'overtime' => $newAvailabilities->getAvailability()->first()->getOvertime(),
                'time_start' => $newAvailabilities->getAvailability()->first()->getStartTime(),
                'time_stop' => $newAvailabilities->getAvailability()->first()->getStopTime(),
                'user_id' => $this->other_user->id,
                'company_id' => $this->company->id,
                'status' => self::CONFIRMED,
                'source' => UserAvailabilitySourceType::INTERNAL,
            ])
        );
        $this->assertDatabaseHas(
            UserAvailability::class,
            array_merge([
                'overtime' => $newAvailabilities->getAvailability()->last()->getOvertime(),
                'time_start' => $newAvailabilities->getAvailability()->last()->getStartTime(),
                'time_stop' => $newAvailabilities->getAvailability()->last()->getStopTime(),
                'user_id' => $this->other_user->id,
                'company_id' => $this->company->id,
                'status' => self::CONFIRMED,
                'source' => UserAvailabilitySourceType::INTERNAL,
            ])
        );
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Success add developer availability
     *
     * @test
     */
    public function success_add_developer_availability()
    {
        //GIVEN
        $newAvailabilities = $this->getNewAvailabilities();

        //WHEN
        $this->own_adder->add($newAvailabilities, $this->company->id, now());

        //THEN
        $this->assertDatabaseHas(
            UserAvailability::class,
            array_merge([
                'overtime' => $newAvailabilities->getAvailability()->first()->getOvertime(),
                'time_start' => $newAvailabilities->getAvailability()->first()->getStartTime(),
                'time_stop' => $newAvailabilities->getAvailability()->first()->getStopTime(),
                'user_id' => $this->user->id,
                'company_id' => $this->company->id,
                'status' => self::ADDED,
                'source' => UserAvailabilitySourceType::INTERNAL,
            ])
        );
        $this->assertDatabaseHas(
            UserAvailability::class,
            array_merge([
                'overtime' => $newAvailabilities->getAvailability()->last()->getOvertime(),
                'time_start' => $newAvailabilities->getAvailability()->last()->getStartTime(),
                'time_stop' => $newAvailabilities->getAvailability()->last()->getStopTime(),
                'user_id' => $this->user->id,
                'company_id' => $this->company->id,
                'status' => self::ADDED,
                'source' => UserAvailabilitySourceType::INTERNAL,
            ])
        );
    }

    /**
     * @feature User Availability
     * @scenario Add availability
     * @case Success add availability by External Tool
     *
     * @test
     */
    public function success_addByExternalTool()
    {
        //GIVEN
        $start_time = '10:10:10';
        $stop_time = '11:11:11';
        $description = 'description';
        $overtime = false;
        $available = false;
        $source = UserAvailabilitySourceType::EXTERNAL;
        $newAvailabilities = $this->makeUserAvailability($start_time,$stop_time, $description, $overtime, $available, $source);

        //WHEN
        $this->admin_adder->add($newAvailabilities, $this->company->id, now());

        //THEN
        $this->assertDatabaseHas(
            UserAvailability::class,
            [
                'overtime' => $overtime,
                'time_start' => $start_time,
                'time_stop' => $stop_time,
                'user_id' => $this->other_user->id,
                'company_id' => $this->company->id,
                'status' => self::CONFIRMED,
                'source' => UserAvailabilitySourceType::EXTERNAL,
            ]
        );
    }

}
