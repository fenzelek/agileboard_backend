<?php

namespace App\Listeners;

use App\Events\Event;
use App\Models\Db\User;
use App\Notifications\Activation;

class SendActivationEmail
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
        $user = $event->user;

        if (! $user->isActivated()) {
            $initial_language = config('app.locale');
            trans()->setLocale($event->language);

            $user->notify(new Activation($event->url));
            trans()->setLocale($initial_language);
        }
    }
}
