<?php
declare(strict_types=1);

namespace App\Modules\Interaction\Models\Dto;

use App\Interfaces\Interactions\IInteractionRequest;
use App\Models\Db\Interaction as InteractionModel;
use App\Modules\Interaction\Contracts\IInteractionDTO;
use Illuminate\Support\Collection;

class InteractionDTO implements IInteractionDTO
{
    private InteractionModel $interaction_model;
    private IInteractionRequest $request;

    public function __construct(InteractionModel $interaction_model, IInteractionRequest $request)
    {
        $this->interaction_model = $interaction_model;
        $this->request = $request;
    }

    public function getAuthorId(): int
    {
        return $this->interaction_model->user_id;
    }

    public function getProjectId(): int
    {
        return $this->interaction_model->project_id;
    }

    public function getEventType(): string
    {
        return $this->interaction_model->event_type;
    }

    public function getSourceType(): string
    {
        return $this->interaction_model->source_type;
    }

    public function getSourceId(): int
    {
        return $this->interaction_model->source_id;
    }

    public function getInteractionPings(): Collection
    {
        return $this->request->getInteractionPings();
    }

    public function getCompanyId(): int
    {
        return $this->interaction_model->company_id;
    }

    public function getActionType(): string
    {
        return $this->interaction_model->action_type;
    }
}