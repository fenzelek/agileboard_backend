<?php

namespace App\Modules\User\Events;

use App\Events\Event;
use App\Models\Db\User;
use Illuminate\Queue\SerializesModels;

class UserWasActivated extends Event
{
    use SerializesModels;

    /**
     * User that was activated.
     *
     * @var User
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
