<?php

declare(strict_types=1);

namespace App\Modules\Integration\Models;

class ActivitySummaryExportDto
{
    private ?int $ticket_id;

    private string $ticket_title;

    private string $ticket_name;

    private string $ticket_description;

    private int $estimate;

    private int $total_time;

    private string $user_first_name;

    private string $user_last_name;

    private string $sprint_name;

    private string $project_name;

    public function __construct(
        ?int $ticket_id,
        string $ticket_title,
        string $ticket_name,
        string $ticket_description,
        int $estimate,
        int $total_time,
        string $user_first_name,
        string $user_last_name,
        string $sprint_name,
        string $project_name
    ) {
        $this->ticket_id = $ticket_id;
        $this->ticket_title = $ticket_title;
        $this->ticket_name = $ticket_name;
        $this->ticket_description = $ticket_description;
        $this->estimate = $estimate;
        $this->total_time = $total_time;
        $this->user_first_name = $user_first_name;
        $this->user_last_name = $user_last_name;
        $this->sprint_name = $sprint_name;
        $this->project_name = $project_name;
    }

    public function getTicketId(): ?int
    {
        return $this->ticket_id;
    }

    public function getTicketTitle(): ?string
    {
        return $this->ticket_title;
    }

    public function getTicketName(): ?string
    {
        return $this->ticket_name;
    }

    public function getTicketDescription(): ?string
    {
        return $this->ticket_description;
    }

    public function getEstimate(): int
    {
        return $this->estimate;
    }

    public function getUserFirstName(): string
    {
        return $this->user_first_name;
    }

    public function getUserLastName(): string
    {
        return $this->user_last_name;
    }

    public function getSprintName(): string
    {
        return $this->sprint_name;
    }

    public function getProjectName(): string
    {
        return $this->project_name;
    }

    public function getTotalTime(): int
    {
        return $this->total_time;
    }
}
