<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Notification\Services\NotificationReader;

use App\Modules\Notification\Models\DatabaseNotification;
use App\Modules\Notification\Models\Descriptors\FailReason;
use App\Modules\Notification\Services\DatabaseNotificationService;
use App\Modules\Notification\Services\NotificationReader;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationReaderTest extends TestCase
{
    use DatabaseTransactions;
    use NotificationReaderTrait;

    private NotificationReader $service;

    /**
     * @feature Notification
     * @scenario Read notifications
     * @case All notification ids are valid
     *
     * @test
     */
    public function read_WhenValidNotificationsProvided_ShouldReturnSuccess(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();

        $valid_notification_ids = [
            $this->createUserNotification($user, $company->id)->id,
            $this->createUserNotification($user, $company->id)->id,
        ];

        //WHEN
        $result = $this->service->read($valid_notification_ids, $user->id, $company->id);

        //THEN
        $this->assertTrue($result->success());
        $this->assertNotificationRead($valid_notification_ids[0]);
        $this->assertNotificationRead($valid_notification_ids[0]);
    }

    /**
     * @feature Notification
     * @scenario Read notifications
     * @case Notification id from other company provided
     *
     * @test
     */
    public function read_WhenNotificationFromOtherCompanyProvided_ShouldReturnFail(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $other_company = $this->createCompany();
        $notification_ids = [
            $this->createUserNotification($user, $company->id)->id,
            $this->createUserNotification($user, $other_company->id)->id,
        ];
        $expected_invalid_ids = [$notification_ids[1]];

        //WHEN
        $result = $this->service->read($notification_ids, $user->id, $company->id);

        //THEN
        $this->assertFalse($result->success());
        $this->assertSame(FailReason::INVALID_NOTIFICATION_IDS, $result->getFailReason());
        $this->assertEquals($expected_invalid_ids, $result->getErrors()['invalid_notification_ids']);
    }

    /**
     * @feature Notification
     * @scenario Read notifications
     * @case When read notifications provided
     *
     * @test
     */
    public function read_WhenReadNotificationsProvided_ShouldReturnFail(): void
    {
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $notification_ids = [
            $this->createUserNotification($user, $company->id)->id,
            $this->createUserNotification($user, $company->id)->id,
        ];
        $user->unreadNotifications()->update(['read_at' => Carbon::now()]);

        //WHEN
        $result = $this->service->read($notification_ids, $user->id, $company->id);

        //THEN
        $this->assertFalse($result->success());
        $this->assertSame(FailReason::INVALID_NOTIFICATION_IDS, $result->getFailReason());
        $this->assertEquals($notification_ids, $result->getErrors()['invalid_notification_ids']);
    }

    /**
     * @feature Notification
     * @scenario Read notifications
     * @case Invalid notification ids provided
     *
     * @test
     */
    public function read_WhenInvalidNotificationsProvided_ShouldReturnFailWithMessage(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $valid_notification_ids = [
            $this->createUserNotification($user, $company->id)->id,
            $this->createUserNotification($user, $company->id)->id,
        ];
        $invalid_notification_ids = ['test-test', 'invalid-id-112312'];

        //WHEN
        $result = $this->service->read(
            array_merge($valid_notification_ids, $invalid_notification_ids),
            $user->id,
            $company->id
        );

        //THEN
        $this->assertFalse($result->success());
        $this->assertSame(FailReason::INVALID_NOTIFICATION_IDS, $result->getFailReason());
        $this->assertEquals($invalid_notification_ids, $result->getErrors()['invalid_notification_ids']);
        $this->assertNotificationNotRead($valid_notification_ids[0]);
        $this->assertNotificationNotRead($valid_notification_ids[1]);
    }

    /**
     * @feature Notification
     * @scenario Read all notifications
     * @case Logged user
     *
     * @test
     */
    public function readAll_ShouldReadOnlyAllUserNotificationsInSelectedCompany(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $other_company = $this->createCompany();
        $other_user = $this->createNewUser();

        $user_notification_in_company = $this->createUserNotification($user, $company->id);
        $user_notification_in_other_company= $this->createUserNotification($user, $other_company->id);
        $other_user_notification_in_company = $this->createUserNotification($other_user, $company->id);

        //WHEN
        $result = $this->service->readAll($user->id, $company->id);

        //THEN
        $this->assertTrue($result->success());
        $this->assertNotificationRead($user_notification_in_company->id);
        $this->assertNotificationNotRead($user_notification_in_other_company->id);
        $this->assertNotificationNotRead($other_user_notification_in_company->id);
    }

    protected function setUp(): void
    {
        parent::setUp();
        DatabaseNotification::query()->delete();
        $this->service = $this->app->make(NotificationReader::class);
    }
}
