<?php

declare(strict_types=1);

namespace App\Models\Notification\Contracts;

use App\Interfaces\Interactions\INotificationPingDTO;

interface IInteractionNotificationManager
{
    public function notify(INotificationPingDTO $interaction): ISendResult;
}
