<?php

declare(strict_types=1);

namespace App\Modules\Integration\Models;

use Carbon\Carbon;

class ActivityExportDto
{
    private int $id;

    private string $user_first_name;

    private string $user_last_name;

    private ?Carbon $utc_started_at;

    private ?Carbon $utc_finished_at;

    private int $tracked;

    private string $project_name;

    private string $sprint_name;

    private ?string $ticket_title;

    private string $comment;

    public function __construct(
        int $id,
        string $user_first_name,
        string $user_last_name,
        ?Carbon $utc_started_at,
        ?Carbon $utc_finished_at,
        int $tracked,
        string $project_name,
        string $sprint_name,
        ?string $ticket_title,
        string $comment
    ) {
        $this->id = $id;
        $this->user_first_name = $user_first_name;
        $this->user_last_name = $user_last_name;
        $this->utc_started_at = $utc_started_at;
        $this->utc_finished_at = $utc_finished_at;
        $this->tracked = $tracked;
        $this->project_name = $project_name;
        $this->sprint_name = $sprint_name;
        $this->ticket_title = $ticket_title;
        $this->comment = $comment;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTracked(): int
    {
        return $this->tracked;
    }

    public function getUserFirstName(): string
    {
        return $this->user_first_name;
    }

    public function getUserLastName(): string
    {
        return $this->user_last_name;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getProjectName(): string
    {
        return $this->project_name;
    }

    public function getSprintName(): string
    {
        return $this->sprint_name;
    }

    public function getTicketTitle(): ?string
    {
        return $this->ticket_title;
    }

    public function getUtcStartedAt(): ?Carbon
    {
        return $this->utc_started_at;
    }

    public function getUtcFinishedAt(): ?Carbon
    {
        return $this->utc_finished_at;
    }
}
