<?php
declare(strict_types=1);

namespace App\Models\Other\Interaction;

use App\Interfaces\Interactions\IInteractionDataDto;

class InteractionDataDto implements IInteractionDataDto
{

    private string $interaction_event_type;
    private int $user_id;
    private int $project_id;
    private string $action_type;

    public function __construct(
        string $interaction_event_type,
        string $action_type,
        int $user_id,
        int $project_id
    )
    {
        $this->interaction_event_type = $interaction_event_type;
        $this->user_id = $user_id;
        $this->project_id = $project_id;
        $this->action_type = $action_type;
    }

    /**
     * @return string
     */
    public function getActionType(): string
    {
        return $this->action_type;
    }

    /**
     * @return string
     */
    public function getInteractionEventType(): string
    {
        return $this->interaction_event_type;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->project_id;
    }
}