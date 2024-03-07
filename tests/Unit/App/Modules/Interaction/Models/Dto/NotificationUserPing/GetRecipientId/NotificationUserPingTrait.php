<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Interaction\Models\Dto\NotificationUserPing\GetRecipientId;

use App\Modules\Interaction\Contracts\IInteractionDTO;
use App\Modules\Interaction\Contracts\IInteractionPing;
use Mockery as m;

trait NotificationUserPingTrait
{
    private function mockInteractionPing(int $recipientId): IInteractionPing
    {
        $interaction_ping = m::mock(IInteractionPing::class);
        $interaction_ping->allows('getRecipientId')->once()->andReturns($recipientId);
        return $interaction_ping;

    }

    private function mockInteraction(): IInteractionDTO
    {
        return m::mock(IInteractionDTO::class);
    }
}
