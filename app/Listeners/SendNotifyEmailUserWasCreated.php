<?php

namespace App\Listeners;

use App\Events\Event;
use App\Models\Db\User;
use App\Notifications\CreatedNewUser;

class SendNotifyEmailUserWasCreated
{
    /**
     * Handle the event.
     *
     * @param  Event $event
     *
     * @return void
     */
    public function handle(Event $event)
    {
        /** @var User $user */
        $event->user->notify(new CreatedNewUser());
    }
}
