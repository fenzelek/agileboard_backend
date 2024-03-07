<?php
declare(strict_types=1);

namespace App\Models\Notification;

use App\Models\Other\Interaction\NotifiableType;
use App\Modules\Interaction\Contracts\IInteractionPing;

class InteractionPingDTO implements IInteractionPing
{
    private int $recipient_id;
    private ?string $ref;
    private ?string $message;
    private ?string $notifiable;

    public function __construct(int $recipient_id, string $notifiable = NotifiableType::USER, ?string $ref = null, ?string $message = null)
    {
        $this->recipient_id = $recipient_id;
        $this->ref = $ref;
        $this->message = $message;
        $this->notifiable = $notifiable;
    }

    public function getRecipientId(): int
    {
        return $this->recipient_id;
    }

    public function getNotifiable(): string
    {
        return $this->notifiable;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}