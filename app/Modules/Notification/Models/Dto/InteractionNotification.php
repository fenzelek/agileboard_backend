<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models\Dto;

use Illuminate\Contracts\Support\Arrayable;

class InteractionNotification implements Arrayable
{
    private int $project_id;

    private string $action_type;

    private string $event_type;

    private string $source_type;

    private string $title;

    private string $author_name;

    private ?array $source_properties;

    private string $ref;

    private ?string $message;

    public function __construct(
        int $project_id,
        string $action_type,
        string $event_type,
        string $source_type,
        string $title,
        string $author_name,
        ?array $source_properties,
        string $ref,
        ?string $message
    ) {
        $this->project_id = $project_id;
        $this->action_type = $action_type;
        $this->event_type = $event_type;
        $this->source_type = $source_type;
        $this->title = $title;
        $this->author_name = $author_name;
        $this->source_properties = $source_properties;
        $this->ref = $ref;
        $this->message = $message;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function getActionType(): string
    {
        return $this->action_type;
    }

    public function getSourceType(): string
    {
        return $this->source_type;
    }

    public function getEventType(): string
    {
        return $this->event_type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthorName(): string
    {
        return $this->author_name;
    }

    public function getSourceProperties(): ?array
    {
        return $this->source_properties;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->project_id,
            'title' => $this->title,
            'action_type' => $this->action_type,
            'source_type' => $this->source_type,
            'event_type' => $this->event_type,
            'author_name' => $this->author_name,
            'source_properties' => $this->formatSourceProperties(),
            'ref' => $this->ref,
            'message' => $this->message,
        ];
    }

    private function formatSourceProperties(): ?array
    {
        if ($this->source_properties === null) {
            return null;
        }
        return array_map(function (SourceProperty $source_property) {
            return [
                'type' => $source_property->getType(),
                'id' => $source_property->getId(),
            ];
        }, $this->source_properties);
    }
}
