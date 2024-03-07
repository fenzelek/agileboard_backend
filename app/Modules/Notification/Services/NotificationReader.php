<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Models\Db\User;
use App\Modules\Notification\Models\Descriptors\FailReason;
use App\Modules\Notification\Models\Dto\ReadResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationReader
{
    public function read(array $notification_ids, int $user_id, int $company_id): ReadResult
    {
        $user = $this->findUser($user_id);
        $invalid_ids = $this->detectInvalidNotifications($user, $notification_ids, $company_id);

        if (count($invalid_ids)>0) {
            return new ReadResult(false, FailReason::INVALID_NOTIFICATION_IDS, [
                'invalid_notification_ids' => $invalid_ids,
            ]);
        }
        $this->markNotificationsAsRead($user, $notification_ids, $company_id);

        return new ReadResult(true);
    }

    public function readAll(int $user_id, int $company_id): ReadResult
    {
        $user = $this->findUser($user_id);
        $this->markAllNotificationsAsRead($user, $company_id);

        return new ReadResult(true);
    }

    private function markAllNotificationsAsRead(User $user, int $company_id): void
    {
        $user->unreadNotifications()
            ->byCompany($company_id)
            ->update([
                'read_at' => Carbon::now(),
            ]);
    }

    private function markNotificationsAsRead(User $user, array $notification_ids, int $company_id): void
    {
        $user->unreadNotifications()
            ->byCompany($company_id)
            ->whereIn('id', $notification_ids)
            ->update([
                'read_at' => Carbon::now(),
            ]);
    }

    private function detectInvalidNotifications(User $user, array $notification_ids, int $company_id): array
    {
        $database_notification_ids = $this->findNotificationIds($user, $notification_ids, $company_id);

        $invalid_ids = [];
        foreach ($notification_ids as $notification_id) {
            if (! $database_notification_ids->contains($notification_id)) {
                $invalid_ids[] = $notification_id;
            }
        }

        return $invalid_ids;
    }

    private function findNotificationIds(User $user, array $notification_ids, int $company_id): Collection
    {
        return $user->unreadNotifications()
            ->byCompany($company_id)
            ->whereIn('id', $notification_ids)
            ->select('id')
            ->pluck('id');
    }

    private function findUser(int $user_id): User
    {
        /** @var User */
        return User::query()->findOrFail($user_id);
    }
}
