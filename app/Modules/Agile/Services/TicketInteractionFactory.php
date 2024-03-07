<?php

declare(strict_types=1);

namespace App\Modules\Agile\Services;

use App\Interfaces\Interactions\IInteractionFacade;
use App\Interfaces\Interactions\IInteractionRequest;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Notification\InvolvedInteractionDTO;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionDataDto;
use App\Models\Other\Interaction\InteractionEventType;
use Illuminate\Support\Collection;

class TicketInteractionFactory
{
    private IInteractionFacade $interaction_facade;

    public function __construct(IInteractionFacade $interaction_facade)
    {
        $this->interaction_facade = $interaction_facade;
    }

    public function forNewTicket(IInteractionRequest $request, Ticket $ticket, int $project_id, int $user_id): void
    {
        if ($request->getInteractionPings()->isEmpty())
        {
            return;
        }

        $interaction_data_dto = new InteractionDataDto(
            InteractionEventType::TICKET_NEW,
            ActionType::PING,
            $user_id,
            $project_id,
        );
        $this->interaction_facade->create($interaction_data_dto, $ticket, $request);
    }

    public function forTicketEdit(IInteractionRequest $request, Ticket $ticket, int $project_id, int $user_id): void
    {
        if ($request->getInteractionPings()->isEmpty())
        {
            return;
        }

        $interaction_data_dto = new InteractionDataDto(
            InteractionEventType::TICKET_EDIT,
            ActionType::PING,
            $user_id,
            $project_id,
        );
        $this->interaction_facade->create($interaction_data_dto, $ticket, $request);
    }

    public function forNewComment(IInteractionRequest $request, TicketComment $ticket_comment, int $project_id, int $user_id): void
    {
        if ($request->getInteractionPings()->isEmpty())
        {
            return;
        }

        $interaction_data_dto = new InteractionDataDto(
            InteractionEventType::TICKET_COMMENT_NEW,
            ActionType::PING,
            $user_id,
            $project_id,
        );
        $this->interaction_facade->create($interaction_data_dto, $ticket_comment, $request);
    }

    public function forCommentEdit(IInteractionRequest $request, TicketComment $ticket_comment, int $project_id, int $user_id): void
    {
        if ($request->getInteractionPings()->isEmpty())
        {
            return;
        }

        $interaction_data_dto = new InteractionDataDto(
            InteractionEventType::TICKET_COMMENT_EDIT,
            ActionType::PING,
            $user_id,
            $project_id,
        );
        $this->interaction_facade->create($interaction_data_dto, $ticket_comment, $request);
    }

    public function forInvolvedAssigned(
        Collection $new_involved_ids,
        Ticket $ticket,
        int $company_id,
        int $project_id,
        int $user_id): void
    {
        if ($new_involved_ids->isEmpty())
        {
            return;
        }

        $interaction_data_dto = new InteractionDataDto(
            InteractionEventType::TICKET_INVOLVED_ASSIGNED,
            ActionType::INVOLVED,
            $user_id,
            $project_id,
        );

        $involved_notification = new InvolvedInteractionDTO($company_id, $new_involved_ids);

        $this->interaction_facade->create($interaction_data_dto, $ticket, $involved_notification);
    }

    public function forInvolvedDeleted(
        Collection $involved_ids,
        Ticket $ticket,
        Project $project,
        int $user_id): void
    {
        if ($involved_ids->isEmpty())
        {
            return;
        }

        $interaction_data_dto = new InteractionDataDto(
            InteractionEventType::TICKET_INVOLVED_DELETED,
            ActionType::INVOLVED,
            $user_id,
            $project->id,
        );

        $involved_notification = new InvolvedInteractionDTO($project->company_id, $involved_ids);

        $this->interaction_facade->create($interaction_data_dto, $ticket, $involved_notification);
    }
}
