<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\DestroyerAvailability;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\DestroyerAvailability;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\CalendarAvailability\Services\AvailabilityTools\AvailabilityToolsTrait;
use function auth;
use function factory;
use function now;

class DestroyerAvailabilityTest extends TestCase
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

        $this->admin_destroyer =
            $this->app->make(
                DestroyerAvailability::class,
                ['user' => $this->other_user]
            );
    }

    /**
     * @feature User Availability
     * @scenario Destroy availability
     * @case destroy today when user admin, destroy other user
     *
     * @test
     */
    public function destroy_today_availabilities_when_user_admin()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        //WHEN
        $this->admin_destroyer->destroy($this->company->id, $today);

        //THEN
        $this->assertDatabaseCount(UserAvailability::class, 8);
        $this->assertDBHas(0, $today, $this->other_user);
        $this->assertDBHas(4, $today, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->other_user);
    }

    /**
     * @feature User Availability
     * @scenario Destroy availability
     * @case destroy tomorrow availability when user admin, destroy other user
     *
     * @test
     */
    public function destroy_tomorrow_availabilities_when_user_admin()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        //WHEN
        $this->admin_destroyer->destroy($this->company->id, $tomorrow);

        //THEN
        $this->assertDatabaseCount(UserAvailability::class, 8);
        $this->assertDBHas(2, $today, $this->other_user);
        $this->assertDBHas(4, $today, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->user);
        $this->assertDBHas(0, $tomorrow, $this->other_user);
    }

    /**
     * @feature User Availability
     * @scenario Destroy availability
     * @case destroy tomorrow availability when user admin
     *
     * @test
     */
    public function destroy_availabilities_when_user_admin_different_company()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $different_company = factory(Company::class)->create();
        $this->user->setSelectedCompany($different_company->id);

        //WHEN
        $this->admin_destroyer->destroy($different_company->id, $tomorrow);

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
        $this->admin_destroyer->destroy($this->company->id, $wrong_day);

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
     * @case destroy availability when user admin wrong day
     *
     * @test
     */
    public function destroy_availabilities_when_user_admin_wrong_day()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');
        $wrong_day = now()->subDay()->format('Y-m-d');

        //WHEN
        $this->admin_destroyer->destroy($this->company->id, $wrong_day);

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
     * @case destroy today availability when user admin, self availability
     *
     * @test
     */
    public function destroy_availabilities_when_user_admin_self()
    {
        //GIVEN
        $this->setDataAvailabilityInDB();
        $this->assertDatabaseCount(UserAvailability::class, 10);
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $admin_destroyer =
            $this->app->make(
                DestroyerAvailability::class,
                ['user' => $this->user]
            );

        //WHEN
        $admin_destroyer->destroy($this->company->id, $today);

        //THEN
        $this->assertDatabaseCount(UserAvailability::class, 6);
        $this->assertDBHas(2, $today, $this->other_user);
        $this->assertDBHas(0, $today, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->user);
        $this->assertDBHas(2, $tomorrow, $this->other_user);
    }
}
