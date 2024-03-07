<?php

namespace  App\Modules\Notification\Services;

use App\Events\EventInterface;

interface NotificationChannelInterface
{
    public function __construct(EventInterface $event);

    /**
     * @param $notifiable
     * @return mixed
     */
    public function via($notifiable);

    /**
     * Get recipients for Notification::send.
     * @return mixed
     */
    public function getRecipients();
}
