<?php

namespace App\Modules\Notification\Models\Dto;

use App\Interfaces\Interactions\INotificationPingDTO;

class NotificationPingDTO implements INotificationPingDTO
{
    private int $company_id;

    private int $author_id;

    private int $recipient_id;

    private int $project_id;

    private string $event_type;

    private string $action_type;

    private string $source_type;

    private int $source_id;

    private ?string $ref;

    private ?string $message;

    public function __construct(
        int $company_id,
        int $author_id,
        int $recipient_id,
        int $project_id,
        string $event_type,
        string $action_type,
        string $source_type,
        int $source_id,
        ?string $ref,
        ?string $message
    ) {
        $this->company_id = $company_id;
        $this->author_id = $author_id;
        $this->recipient_id = $recipient_id;
        $this->project_id = $project_id;
        $this->event_type = $event_type;
        $this->action_type = $action_type;
        $this->source_type = $source_type;
        $this->source_id = $source_id;
        $this->ref = $ref;
        $this->message = $message;
    }

    public function getSelectedCompanyId(): int
    {
        return $this->company_id;
    }

    public function getAuthorId(): int
    {
        return $this->author_id;
    }

    public function getRecipientId(): int
    {
        return $this->recipient_id;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function getEventType(): string
    {
        return $this->event_type;
    }

    public function getActionType(): string
    {
        return $this->action_type;
    }

    public function getSourceType(): string
    {
        return $this->source_type;
    }

    public function getSourceId(): int
    {
        return $this->source_id;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
