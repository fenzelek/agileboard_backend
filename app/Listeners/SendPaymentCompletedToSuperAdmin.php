<?php

namespace App\Listeners;

use App\Events\Event;
use App\Notifications\PaymentCompleted;

class SendPaymentCompletedToSuperAdmin
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
        try {
            $event->user->notify(new PaymentCompleted($event->payment));
        } catch (\Exception $exception) {
            \Log::error([get_class($exception), $exception->getMessage()]);
        }
    }
}
