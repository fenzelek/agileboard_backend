<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\DestroyerAvailability;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\DestroyerAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\DestroyerOwnAvailability;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\AvailabilityToolsTrait;
use function auth;
use function factory;
use function now;

class DestroyerOwnAvailabilityTest extends TestCase
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
     * @var DestroyerAvailability
     */
    private DestroyerAvailability $own_destroyer;
    /**
     * @var DestroyerAvailability
     */
    private DestroyerAvailability $admin_destroyer;

    const CONFIRMED = 'CONFIRMED';

    public function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->user->setSelectedCompany($this->company->id);

        $this->other_user = factory(User::class)->create();

        $this->own_destroyer =
            $this->app->make(
                DestroyerOwnAvailability::class,
                ['user' => $this->user]
            );
    }

    /**
     * @feature User Availability
     * @scenario Destroy availability
     * @case destroy today when user developer
     *
     * @test
     */
    public function destroy_today_availabilities_when_user_developer()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        //WHEN
        $this->own_destroyer->destroy($this->company->id, $today);

        //THEN
        $this->assertDatabaseCount(UserAvailability::class, 8);
        $this->assertDBHas(2, $today, $this->other_user);
        $this->assertDBHas(2, $today, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->other_user);
    }

    /**
     * @feature User Availability
     * @scenario Destroy availability
     * @case destroy tomorrow availability when user developer
     *
     * @test
     */
    public function destroy_tomorrow_availabilities_when_user_developer()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        //WHEN
        $this->own_destroyer->destroy($this->company->id, $tomorrow);

        //THEN
        $this->assertDatabaseCount(UserAvailability::class, 9);
        $this->assertDBHas(2, $today, $this->other_user);
        $this->assertDBHas(4, $today, $this->user);
        $this->assertDBHas(1, $tomorrow, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->other_user);
    }

    /**
     * @feature User Availability
     * @scenario Destroy availability
     * @case destroy tomorrow availability when user developer
     *
     * @test
     */
    public function destroy_availabilities_when_user_developer_in_different_company()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $different_company = factory(Company::class)->create();
        $this->user->setSelectedCompany($different_company->id);

        //WHEN
        $this->own_destroyer->destroy($different_company->id, $tomorrow);

        //THEN
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $this->assertDBHas(2, $today, $this->other_user);
        $this->assertDBHas(4, $today, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->other_user);
    }

    /**
     * @feature User Availability
     * @scenario Destroy availability
     * @case destroy availability when user developer wrong day
     *
     * @test
     */
    public function destroy_availabilities_when_user_developer_wrong_day()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');
        $wrong_day = now()->subDay()->format('Y-m-d');

        //WHEN
        $this->own_destroyer->destroy($this->company->id, $wrong_day);

        //THEN
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $this->assertDBHas(2, $today, $this->other_user);
        $this->assertDBHas(4, $today, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->other_user);
    }
}
