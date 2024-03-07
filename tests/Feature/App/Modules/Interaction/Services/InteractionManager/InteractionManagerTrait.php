<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Interaction\Services\InteractionManager;

use App\Interfaces\Interactions\IInteractionRequest;
use App\Models\Db\Interaction;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use Illuminate\Support\Collection;
use Mockery as m;

trait InteractionManagerTrait
{
    protected function mockRequest(Collection $interaction_pings): IInteractionRequest
    {
        $mock = m::mock(IInteractionRequest::class);

        $mock->shouldReceive('getInteractionPings')->andReturn($interaction_pings);

        return $mock;
    }
    protected function createInteraction(array $data=[]): Interaction
    {
        return factory(Interaction::class)->create($data);
    }

    protected function createProject(): Project
    {
        return factory(Project::class)->create();
    }

    protected function createTicket(): Ticket
    {
        return factory(Ticket::class)->create();
    }
}
