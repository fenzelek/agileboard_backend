<?php

declare(strict_types=1);

namespace App\Modules\Interaction\Models\Dto;

use App\Interfaces\Interactions\INotificationPingDTO;
use App\Models\Db\User;
use App\Modules\Interaction\Contracts\IInteractionDTO;
use App\Modules\Interaction\Contracts\IInteractionPing;

class NotificationGroupPingDTO extends NotificationPingDTO implements INotificationPingDTO
{
    private User $user;
    private IInteractionDTO $interaction;

    public function __construct(IInteractionDTO $interaction, IInteractionPing $interaction_ping, User $user)
    {
        parent::__construct($interaction, $interaction_ping);
        $this->user = $user;
        $this->interaction = $interaction;
    }

    public function getRecipientId(): int
    {
        return $this->user->id;
    }
}
