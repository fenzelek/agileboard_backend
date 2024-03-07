<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Notification\Http\Controllers\NotificationController;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Modules\Notification\Models\DatabaseNotification;
use App\Notifications\Notification;
use Illuminate\Support\Str;

trait NotificationControllerTrait
{
    protected function notificationsSuccessJsonStructure(): array
    {
        return [
            'data' => [
                [
                    'id',
                    'type',
                    'created_at',
                    'read_at',
                    'company_id',
                    'data',
                ],
            ],
            'current_page',
            'per_page',
        ];
    }
    
    protected function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    protected function createReadNotification(User $user, int $company_id): void
    {
        $notification = $this->createUnreadNotification($user, $company_id);
        $notification->markAsRead();
    }

    protected function createUnreadNotification(User $user, int $company_id): DatabaseNotification
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
