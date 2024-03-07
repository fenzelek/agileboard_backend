<?php

declare(strict_types=1);

namespace App\Modules\Interaction\Services;

use App\Interfaces\Interactions\IInteractionable;
use App\Interfaces\Interactions\IInteractionDataDto;
use App\Interfaces\Interactions\IInteractionRequest;
use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Modules\Interaction\Contracts\IInteractionPing;

class InteractionService
{
    public function create(
        IInteractionDataDto $interaction_data_dto,
        IInteractionable $interactionable,
        IInteractionRequest $interaction_request
    ): Interaction {
        $interaction = new Interaction([
            'user_id' => $interaction_data_dto->getUserId(),
            'project_id' => $interaction_data_dto->getProjectId(),
            'event_type' => $interaction_data_dto->getInteractionEventType(),
            'action_type' => $interaction_data_dto->getActionType(),
            'company_id' => $interaction_request->getSelectedCompanyId(),
        ]);

        $interactionable->interactions()->save($interaction);

        /** @var IInteractionPing $interaction_ping_request */
        foreach ($interaction_request->getInteractionPings() as $interaction_ping_request) {
            $interaction_ping = $this->getInteractionPing($interaction_ping_request);
            $interaction->interactionPings()->save($interaction_ping);
        }

        return $interaction;
    }

    private function getInteractionPing(IInteractionPing $interaction_ping): InteractionPing
    {
        return new InteractionPing([
            'recipient_id' => $interaction_ping->getRecipientId(),
            'ref' => $interaction_ping->getRef(),
            'message' => $interaction_ping->getMessage(),
            'notifiable' => $interaction_ping->getNotifiable(),
        ]);
    }
}
