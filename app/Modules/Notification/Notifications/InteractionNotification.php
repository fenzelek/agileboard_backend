<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

use App\Interfaces\Interactions\INotificationPingDTO;
use App\Notifications\Notification;

class InteractionNotification extends Notification
{
    private INotificationPingDTO $interaction;

    public function __construct(INotificationPingDTO $interaction)
    {
        parent::__construct($interaction->getSelectedCompanyId());
        $this->interaction = $interaction;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(): array
    {
        return [
            'project_id' => $this->interaction->getProjectId(),
            'author_id' => $this->interaction->getAuthorId(),
            'action_type' => $this->interaction->getActionType(),
            'event_type' => $this->interaction->getEventType(),
            'source_type' => $this->interaction->getSourceType(),
            'source_id' => $this->interaction->getSourceId(),
            'ref' => $this->interaction->getRef(),
            'message' => $this->interaction->getMessage(),
        ];
    }
}
