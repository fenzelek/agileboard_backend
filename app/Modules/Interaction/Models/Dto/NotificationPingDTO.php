<?php

declare(strict_types=1);

namespace App\Modules\Interaction\Models\Dto;

use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Interaction\Contracts\IInteractionDTO;
use App\Modules\Interaction\Contracts\IInteractionPing;

abstract class NotificationPingDTO
{
    private IInteractionDTO $interaction;
    private IInteractionPing $interaction_ping;

    public function __construct(IInteractionDTO $interaction, IInteractionPing $interaction_ping)
    {
        $this->interaction = $interaction;
        $this->interaction_ping = $interaction_ping;
    }

    public function getAuthorId(): int
    {
        return $this->interaction->getAuthorId();
    }

    public function getProjectId(): int
    {
        return $this->interaction->getProjectId();
    }

    public function getEventType(): string
    {
        return $this->interaction->getEventType();
    }

    public function getActionType(): string
    {
        return $this->interaction->getActionType();
    }

    public function getSourceType(): string
    {
        return $this->interaction->getSourceType();
    }

    public function getSourceId(): int
    {
        return $this->interaction->getSourceId();
    }

    public function getRef(): ?string
    {
        return $this->interaction_ping->getRef();
    }

    public function getMessage(): ?string
    {
        return $this->interaction_ping->getMessage();
    }

    public function getSelectedCompanyId(): int
    {
        return $this->interaction->getCompanyId();
    }
}
