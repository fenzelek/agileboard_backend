<?php
declare(strict_types=1);

namespace App\Modules\Agile\Http\Requests;

use App\Modules\Interaction\Contracts\IInteractionPing;

class InteractionPingRequest implements IInteractionPing
{
    private array $interaction;

    /**
     * @param mixed $item
     */
    public function __construct(array $interaction)
    {
        $this->interaction = $interaction;
    }

    public function getRecipientId(): int
    {
        return (int) $this->interaction['recipient_id'];
    }

    public function getRef(): string
    {
        return $this->interaction['ref'];
    }

    public function getNotifiable(): string
    {
        return $this->interaction['notifiable'];
    }

    public function getMessage() : ?string
    {
        return $this->interaction['message'];
    }

}