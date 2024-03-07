<?php

namespace Tests\Unit\App\Modules\Interaction\Services\InteractionManager\AddNotifications;

use App\Modules\Interaction\Contracts\IInteractionDTO;
use App\Modules\Interaction\Contracts\IInteractionPing;
use Illuminate\Support\Collection;
use Mockery as m;

trait InteractionManagerTrait
{
    private function mockInteractionPing(): IInteractionPing
    {
        return m::mock(IInteractionPing::class);
    }

    private function mockInteraction(Collection $interaction_pings): IInteractionDTO
    {
        $interaction_mock = m::mock(IInteractionDTO::class);
        $interaction_mock->allows('getInteractionPings')->once()->andReturns($interaction_pings);

        return $interaction_mock;
    }
}
