<?php

namespace App\Modules\Knowledge\Services;

use App\Interfaces\Interactions\IInteractionFacade;
use App\Interfaces\Interactions\IInteractionRequest;
use App\Interfaces\Involved\IInvolvedRequest;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\KnowledgePageComment;
use App\Models\Db\Project;
use App\Models\Notification\InvolvedInteractionDTO;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Modules\Knowledge\Contracts\ICommentCreateRequest;
use App\Modules\Knowledge\Contracts\IUpdateCommentRequest;
use App\Models\Other\Interaction\InteractionDataDto;

use Illuminate\Support\Collection;

class KnowledgePageInteractionFactory
{
    private IInteractionFacade $interaction_facade;

    public function __construct(IInteractionFacade $interaction_facade)
    {
        $this->interaction_facade = $interaction_facade;
    }

    public function forNewPage(IInteractionRequest $request, KnowledgePage $page, int $project_id, int $user_id): void
    {
        if ($request->getInteractionPings()->isEmpty())
        {
            return;
        }

        $dto = new InteractionDataDto(
            InteractionEventType::KNOWLEDGE_PAGE_NEW,
            ActionType::PING,
            $user_id,
            $project_id
        );
        $this->interaction_facade->create($dto, $page, $request);
    }

    public function forPageEdit(IInteractionRequest $request, KnowledgePage $page, int $project_id, int $user_id): void
    {
        if ($request->getInteractionPings()->isEmpty())
        {
            return;
        }

        $dto = new InteractionDataDto(
            InteractionEventType::KNOWLEDGE_PAGE_EDIT,
            ActionType::PING,
            $user_id,
            $project_id
        );
        $this->interaction_facade->create($dto, $page, $request);
    }

    public function forNewComment(ICommentCreateRequest $request, KnowledgePageComment $comment, int $user_id): void
    {
        if ($request->getInteractionPings()->isEmpty())
        {
            return;
        }

        $dto = new InteractionDataDto(
            InteractionEventType::KNOWLEDGE_PAGE_COMMENT_NEW,
            ActionType::PING,
            $user_id,
            $request->getProjectId()
        );
        $this->interaction_facade->create($dto, $comment, $request);
    }

    public function forCommentEdit(IUpdateCommentRequest $request, KnowledgePageComment $comment, int $user_id): void
    {
        if ($request->getInteractionPings()->isEmpty())
        {
            return;
        }

        $dto = new InteractionDataDto(
            InteractionEventType::KNOWLEDGE_PAGE_COMMENT_EDIT,
            ActionType::PING,
            $user_id,
            $request->getProjectId()
        );
        $this->interaction_facade->create($dto, $comment, $request);
    }

    public function forInvolvedAssigned(
        Collection $new_involved_ids,
        KnowledgePage $knowledge_page,
        int $company_id,
        int $project_id,
        int $user_id): void
    {
        if ($new_involved_ids->isEmpty())
        {
            return;
        }

        $interaction_data_dto = new InteractionDataDto(
            InteractionEventType::KNOWLEDGE_PAGE_INVOLVED_ASSIGNED,
            ActionType::INVOLVED,
            $user_id,
            $project_id,
        );

        $involved_notification = new InvolvedInteractionDTO($company_id, $new_involved_ids);

        $this->interaction_facade->create($interaction_data_dto, $knowledge_page, $involved_notification);
    }

    public function forInvolvedDeleted(
        Collection $involved_ids,
        KnowledgePage $knowledge_page,
        Project $project,
        int $user_id): void
    {
        if ($involved_ids->isEmpty())
        {
            return;
        }

        $interaction_data_dto = new InteractionDataDto(
            InteractionEventType::KNOWLEDGE_PAGE_INVOLVED_DELETED,
            ActionType::INVOLVED,
            $user_id,
            $project->id,
        );

        $involved_notification = new InvolvedInteractionDTO($project->company_id, $involved_ids);

        $this->interaction_facade->create($interaction_data_dto, $knowledge_page, $involved_notification);
    }
}
