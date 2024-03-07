<?php

declare(strict_types=1);

namespace App\Modules\Interaction\Services;

use App\Interfaces\Interactions\INotificationPingDTO;
use App\Models\Notification\Contracts\IInteractionNotificationManager;
use App\Modules\Interaction\Contracts\IInteractionDTO;
use App\Modules\Interaction\Contracts\IInteractionManager;

class InteractionManager implements IInteractionManager
{
    private NotificationPingExtractor $notification_ping_extractor;
    private IInteractionNotificationManager $interaction_notification_manager;

    public function __construct(
        NotificationPingExtractor $notification_ping_extractor,
        IInteractionNotificationManager $interaction_notification_manager
    ) {
        $this->notification_ping_extractor = $notification_ping_extractor;
        $this->interaction_notification_manager = $interaction_notification_manager;
    }

    public function addNotifications(IInteractionDTO $interaction): void
    {
        if ($interaction->getInteractionPings()->isEmpty()) {
            // TODO KN To implement
            return;
        }

        $notification_pings = $this->notification_ping_extractor->extract($interaction);

        /** @var INotificationPingDTO $notification_ping */
        foreach ($notification_pings as $notification_ping) {
            $this->interaction_notification_manager->notify($notification_ping);
        }
    }
}
