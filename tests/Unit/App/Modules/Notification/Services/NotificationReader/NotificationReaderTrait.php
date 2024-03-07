<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Notification\Services\NotificationReader;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Modules\Notification\Models\DatabaseNotification;
use App\Notifications\Notification;
use Illuminate\Support\Str;

trait NotificationReaderTrait
{
    public function assertNotificationRead(string $notification_id)
    {
        $notification = DatabaseNotification::query()
            ->where('id', $notification_id)
            ->firstOrFail();

        $this->assertNotNull(
            $notification->read_at,
            "Failed to assert that notification with id: {$notification_id} with was read"
        );
    }

    public function assertNotificationNotRead(string $notification_id)
    {
        $notification = DatabaseNotification::query()
            ->where('id', $notification_id)
            ->firstOrFail();

        $this->assertNull(
            $notification->read_at,
            "Failed to assert that notification with id: {$notification_id} with was not read"
        );
    }

    protected function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    private function createUserNotification(User $user, int $company_id): DatabaseNotification
    {
        /** @var DatabaseNotification */
        return DatabaseNotification::query()->create([
            'id' => Str::uuid()->toString(),
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'type' => Notification::class,
            'read_at' => null,
            'data' => [],
            'company_id' => $company_id,
        ]);
    }
}
