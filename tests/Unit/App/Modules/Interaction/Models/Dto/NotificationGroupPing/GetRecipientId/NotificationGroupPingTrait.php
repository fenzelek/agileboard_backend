<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Interaction\Models\Dto\NotificationGroupPing\GetRecipientId;

use App\Modules\Interaction\Contracts\IInteractionDTO;
use App\Modules\Interaction\Contracts\IInteractionPing;
use Mockery as m;

trait NotificationGroupPingTrait
{
    private function mockInteractionPing(): IInteractionPing
    {
        return m::mock(IInteractionPing::class);
    }

    private function mockInteraction(): IInteractionDTO
    {
        return m::mock(IInteractionDTO::class);
    }
}
