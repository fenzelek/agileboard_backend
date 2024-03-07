<?php

declare(strict_types=1);

namespace App\Modules\Interaction\Models\Dto;

use App\Interfaces\Interactions\INotificationPingDTO;
use App\Modules\Interaction\Contracts\IInteractionDTO;
use App\Modules\Interaction\Contracts\IInteractionPing;

class NotificationUserPingDTO extends NotificationPingDTO implements INotificationPingDTO
{
    private IInteractionPing $interaction_ping;

    public function __construct(IInteractionDTO $interaction, IInteractionPing $interaction_ping)
    {
        parent::__construct($interaction, $interaction_ping);
        $this->interaction_ping = $interaction_ping;
    }

    public function getRecipientId(): int
    {
        return $this->interaction_ping->getRecipientId();
    }
}
