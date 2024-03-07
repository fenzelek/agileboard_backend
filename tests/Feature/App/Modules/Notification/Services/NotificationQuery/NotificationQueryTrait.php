<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Notification\Services\NotificationQuery;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Modules\Notification\Contracts\Request\INotificationRequest;
use App\Modules\Notification\Models\DatabaseNotification;
use App\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Mockery;

trait NotificationQueryTrait
{
    /**
     * @return Mockery\MockInterface|INotificationRequest
     */
    protected function mockRequest(?bool $read=null, int $page=1, int $per_page=10): INotificationRequest
    {
        $mock = Mockery::mock(INotificationRequest::class);
        $mock->shouldReceive('getReadFilter')->andReturn($read);
        $mock->shouldReceive('getPage')->andReturn($page);
        $mock->shouldReceive('getPerPage')->andReturn($per_page);

        return $mock;
    }

    protected function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    protected function createNotification(User $user, int $company_id, bool $read=false, string $type=Notification::class, array $data=[]): DatabaseNotification
    {
        /** @var DatabaseNotification */
        return DatabaseNotification::query()->create([
            'id' => Str::uuid()->toString(),
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'type' => $type,
            'read_at' => $read ? Carbon::now() : null,
            'data' => $data,
            'company_id' => $company_id,
        ]);
    }
}
