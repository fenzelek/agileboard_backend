<?php

declare(strict_types=1);

namespace App\Interfaces\Interactions;

interface IInteractionFacade
{
    public function create(
        IInteractionDataDto $interaction_data,
        IInteractionable $interaction_source,
        IInteractionRequest $request
    ): void;
}
