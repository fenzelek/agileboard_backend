<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Notification\Http\Controllers\NotificationController;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use DatabaseTransactions;
    use NotificationControllerTrait;

    /**
     * @feature Notification
     * @scenario Get notifications list
     * @case User authorized
     *
     * @test
     */
    public function userNotifications_WhenAuthorized_success(): void
    {
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $this->createReadNotification($user, $company->id);

        //WHEN
        $response = $this->actingAs($user, 'api')
            ->json('GET', route('user.notifications'), [
                'selected_company_id' => $company->id,
            ]);

        $response->assertOk();
        $response->assertJsonStructure($this->notificationsSuccessJsonStructure());
    }

    /**
     * @feature Notification
     * @scenario Get notifications list
     * @case User not authorized
     *
     * @test
     */
    public function userNotifications_WhenNotAuthorized_Unauthorized(): void
    {
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $this->createReadNotification($user, $company->id);

        //WHEN
        $response = $this
            ->json('GET', route('user.notifications'), [
                'selected_company_id' => $company->id,
            ]);

        $response->assertUnauthorized();
    }


    /**
     * @feature Notification
     * @scenario Get unread notifications
     * @case Unread notifications exists
     *
     * @test
     */
    public function unreadCount_ShouldReturnCountOfUnreadNotifications(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $other_user = $this->createNewUser();
        $company = $this->createCompany();
        $other_company = $this->createCompany();
        $this->createUnreadNotification($user, $company->id);
        $this->createUnreadNotification($user, $other_company->id);
        $this->createReadNotification($user, $company->id);
        $this->createUnreadNotification($other_user, $company->id);

        //WHEN
        $response = $this->actingAs($user, 'api')
            ->json('GET', route('user.notifications.unread-count'), [
                'selected_company_id' => $company->id,
            ]);

        //THEN
        $response->assertOk();
        $this->assertSame(1, $response->json()['count']);
    }

    /**
     * @feature Notification
     * @scenario User read notifications
     * @case User is authorized
     *
     * @test
     */
    public function read_WhenUserAuthorizedAndValidNotificationIds_success(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $notification = $this->createUnreadNotification($user, $company->id);

        //WHEN
        $response = $this
            ->actingAs($user, 'api')
            ->put(route('user.notifications.read'), [
                'selected_company_id' => $company->id,
                'notification_ids' => [$notification->id],
            ]);

        //THEN
        $response->assertOk();
        $this->assertEmpty($response->json());
    }

    /**
     * @feature Notification
     * @scenario User read notifications
     * @case User is authorized and invalid notification ids provided
     *
     * @test
     */
    public function read_WhenUserAuthorizedAndInvalidNotificationIds_fail(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $this->createUnreadNotification($user, $company->id);

        //WHEN
        $response = $this
            ->actingAs($user, 'api')
            ->put(route('user.notifications.read'), [
                'selected_company_id' => $company->id,
                'notification_ids' => ['invalid_notification_id'],
            ]);

        //THEN
        $response->assertStatus(400);
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);
        $this->assertNotEmpty($response->json('errors'));
    }

    /**
     * @feature Notification
     * @scenario User read notifications
     * @case User is not authorized
     *
     * @test
     */
    public function read_WhenUserIsNotAuthorized_unauthorized(): void
    {
        //GIVEN
        $company = $this->createCompany();

        //WHEN
        $response = $this
            ->put(route('user.notifications.read'), [
                'selected_company_id' => $company->id,
                'notification_ids' => ['random_id'],
            ]);

        //THEN
        $response->assertUnauthorized();
    }

    /**
     * @feature Notification
     * @scenario User read all notifications
     * @case User is authorized
     *
     * @test
     */
    public function readAll_WhenUserAuthorized_success(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $this->createUnreadNotification($user, $company->id);
        $this->createUnreadNotification($user, $company->id);

        //WHEN
        $response = $this
            ->actingAs($user, 'api')
            ->put(route('user.notifications.read-all'), [
                'selected_company_id' => $company->id,
            ]);

        //THEN
        $response->assertOk();
    }

    /**
     * @feature Notification
     * @scenario User read all notifications
     * @case User is unauthorized
     *
     * @test
     */
    public function readAll_WhenUserIsNotAuthorized_unauthorized(): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $this->createUnreadNotification($user, $company->id);

        //WHEN
        $response = $this->put(route('user.notifications.read-all'), [
            'selected_company_id' => $company->id,
        ]);

        //THEN
        $response->assertUnauthorized();
    }
}
