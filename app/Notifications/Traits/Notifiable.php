<?php

declare(strict_types=1);

namespace App\Notifications\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Modules\Notification\Models\DatabaseNotification;

/**
 * @method static MorphMany|DatabaseNotification unreadNotifications()
 * @method static MorphMany|DatabaseNotification readNotifications()
 */
trait Notifiable
{
    use \Illuminate\Notifications\Notifiable;

    /**
     * @return MorphMany|DatabaseNotification
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
    }
}
