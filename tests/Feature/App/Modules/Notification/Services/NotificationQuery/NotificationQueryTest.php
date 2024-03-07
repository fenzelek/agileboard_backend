<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Notification\Services\NotificationQuery;

use App\Modules\Notification\Services\NotificationQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\Paginator;
use Tests\TestCase;

class NotificationQueryTest extends TestCase
{
    use DatabaseTransactions;
    use NotificationQueryTrait;

    private NotificationQuery $notification_query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notification_query = $this->app->make(NotificationQuery::class);
    }

    /**
     * @feature Notification
     * @scenario Get notifications list
     * @case User has notifications in other company
     *
     * @test
     */
    public function userNotifications_WhenUserHasNotificationsInOtherCompany_ShouldReturnUserNotificationsFromSelectedCompany(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $other_company = $this->createCompany();

        $this->createNotification($user, $other_company->id);
        $expected_notification = $this->createNotification($user, $company->id);

        $request = $this->mockRequest();

        //WHEN
        $result = $this->notification_query->userNotifications($user, $company->id, $request);

        //THEN
        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertSame(1, count($result->items()));
        $this->assertSame($expected_notification->id, $result->items()[0]->getId());
    }

    /**
     * @feature Notification
     * @scenario Get notifications list
     * @case Read filter is true
     *
     * @test
     */
    public function userNotifications_WhenReadFilterIsTrue_ShouldReturnOnlyReadNotifications(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $read = true;

        $this->createNotification($user, $company->id, ! $read);
        $expected_notification = $this->createNotification($user, $company->id, $read);

        $request = $this->mockRequest($read);

        //WHEN
        $result = $this->notification_query->userNotifications($user, $company->id, $request);

        //THEN
        $this->assertSame(1, count($result->items()));
        $this->assertSame($expected_notification->id, $result->items()[0]->getId());
    }

    /**
     * @feature Notification
     * @scenario Get notifications list
     * @case Read filter is false
     *
     * @test
     */
    public function userNotifications_WhenReadFilterIsFalse_ShouldReturnOnlyNotReadNotifications(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $read = true;

        $this->createNotification($user, $company->id, $read);
        $expected_notification = $this->createNotification($user, $company->id, ! $read);

        $request = $this->mockRequest(! $read);

        //WHEN
        $result = $this->notification_query->userNotifications($user, $company->id, $request);

        //THEN
        $this->assertSame(1, count($result->items()));
        $this->assertSame($expected_notification->id, $result->items()[0]->getId());
    }
}
