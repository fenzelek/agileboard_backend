<?php

declare(strict_types=1);

namespace App\Modules\Interaction\Services;

use App\Interfaces\Interactions\IInteractionable;
use App\Interfaces\Interactions\IInteractionDataDto;
use App\Interfaces\Interactions\IInteractionFacade;
use App\Interfaces\Interactions\IInteractionRequest;
use App\Modules\Interaction\Models\Dto\InteractionDTO;

class InteractionFacade implements IInteractionFacade
{
    private InteractionService $interaction_service;
    private InteractionManager $interaction_manager;

    public function __construct(InteractionService $interaction_service, InteractionManager $interaction_manager)
    {
        $this->interaction_service = $interaction_service;
        $this->interaction_manager = $interaction_manager;
    }

    public function create(IInteractionDataDto $interaction_data, IInteractionable $interaction_source, IInteractionRequest $request): void
    {
        $interaction = $this->interaction_service->create($interaction_data, $interaction_source, $request);
        $this->interaction_manager->addNotifications(new InteractionDTO($interaction, $request));
    }
}
