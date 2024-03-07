<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Notification\Models\DatabaseNotification;
use App\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Channels\DatabaseChannel as VendorDatabaseChannel;
use Illuminate\Notifications\DatabaseNotification as VendorDatabaseNotification;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance(VendorDatabaseChannel::class, new DatabaseChannel());
        $this->app->instance(VendorDatabaseNotification::class, new DatabaseNotification());
    }
}
